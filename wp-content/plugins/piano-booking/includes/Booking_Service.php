<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Booking_Service
{
    /**
     * Create a pending booking. Returns booking ID on success, WP_Error on failure.
     *
     * Atomicity: acquires slot lock before wp_insert_post; releases on failure.
     *
     * @param array $data user_id, room_id, start_at, end_at, paid_via (qr|package|cash),
     *                    name, phone, email
     */
    public static function create_pending(array $data): int|\WP_Error
    {
        $required = ['user_id', 'room_id', 'start_at', 'end_at', 'paid_via', 'name', 'phone', 'email'];
        foreach ($required as $k) {
            if (empty($data[$k])) {
                return new \WP_Error('pb_missing_field', "Missing field: {$k}");
            }
        }

        if (!Slot_Lock::acquire((int) $data['room_id'], $data['start_at'], $data['end_at'])) {
            return new \WP_Error('pb_slot_taken', 'Slot is currently held by another user. Please try again.');
        }

        $hours = self::diff_hours($data['start_at'], $data['end_at']);
        $rate  = (float) get_post_meta($data['room_id'], 'hourly_rate', true);
        $amount = number_format($hours * $rate, 2, '.', '');

        // Package payment: deduct credits first, mark as paid
        $status = 'pb_pending';
        $packagePurchaseId = null;
        $amountCredits = null;
        if ($data['paid_via'] === 'package') {
            $userId = (int) $data['user_id'];
            if (Credits_Engine::has_enough($userId, $hours)) {
                $deducted = Credits_Engine::deduct($userId, $hours);
                if (!is_wp_error($deducted) && $deducted > 0) {
                    $packagePurchaseId = $deducted;
                    $status = 'pb_paid';
                    $amountCredits = number_format($hours, 1, '.', '');
                    $amount = '0.00';
                }
            }
            // If insufficient credits, fall through to pending QR
        }

        $bookingId = wp_insert_post([
            'post_type'   => CPT::BOOKING,
            'post_status' => 'pb_pending',
            'post_author' => (int) $data['user_id'],
            'post_title'  => Ref_Generator::next(CPT::BOOKING),
        ], true);

        if (is_wp_error($bookingId)) {
            Slot_Lock::release((int) $data['room_id'], $data['start_at'], $data['end_at']);
            return $bookingId;
        }

        update_post_meta($bookingId, 'room_id',        (int) $data['room_id']);
        update_post_meta($bookingId, 'location_id',    (int) get_post_meta($data['room_id'], 'location_id', true));
        update_post_meta($bookingId, 'start_at',       $data['start_at']);
        update_post_meta($bookingId, 'end_at',         $data['end_at']);
        update_post_meta($bookingId, 'paid_via',       sanitize_text_field($data['paid_via']));
        update_post_meta($bookingId, 'amount_hkd',     $amount);
        update_post_meta($bookingId, 'customer_name',  sanitize_text_field($data['name']));
        update_post_meta($bookingId, 'customer_phone', sanitize_text_field($data['phone']));
        update_post_meta($bookingId, 'customer_email', sanitize_email($data['email']));
        if ($packagePurchaseId) {
            update_post_meta($bookingId, 'package_purchase_id', $packagePurchaseId);
        }
        if ($amountCredits !== null) {
            update_post_meta($bookingId, 'amount_credits', $amountCredits);
        }

        // If using package payment, status is pb_paid (skip QR pending)
        if ($status !== 'pb_pending') {
            wp_update_post(['ID' => $bookingId, 'post_status' => $status]);
        }

        return $bookingId;
    }

    public static function mark_paid(int $bookingId, int $staffId): bool
    {
        if (get_post_status($bookingId) !== 'pb_pending') return false;
        wp_update_post(['ID' => $bookingId, 'post_status' => 'pb_paid']);
        update_post_meta($bookingId, 'staff_verified_by', $staffId);
        update_post_meta($bookingId, 'verified_at', current_time('mysql', true));
        return true;
    }

    public static function mark_rejected(int $bookingId, int $staffId, string $reason): bool
    {
        if (get_post_status($bookingId) !== 'pb_pending') return false;
        wp_update_post(['ID' => $bookingId, 'post_status' => 'pb_cancelled']);
        update_post_meta($bookingId, 'staff_verified_by', (string) $staffId);
        update_post_meta($bookingId, 'cancellation_reason', 'Payment rejected: ' . $reason);
        self::release_lock($bookingId);
        return true;
    }

    public static function mark_cancelled(int $bookingId, string $reason = ''): bool
    {
        $status = get_post_status($bookingId);
        if (!in_array($status, ['pb_pending', 'pb_paid'], true)) return false;
        // Refund package credits if applicable
        if (get_post_meta($bookingId, 'paid_via', true) === 'package') {
            $pid = (int) get_post_meta($bookingId, 'package_purchase_id', true);
            $hours = (float) get_post_meta($bookingId, 'amount_credits', true);
            if ($pid && $hours > 0) Credits_Engine::refund($pid, $hours);
        }
        wp_update_post(['ID' => $bookingId, 'post_status' => 'pb_cancelled']);
        if ($reason) update_post_meta($bookingId, 'cancellation_reason', $reason);
        self::release_lock($bookingId);
        return true;
    }

    public static function mark_completed(int $bookingId): bool
    {
        if (get_post_status($bookingId) !== 'pb_paid') return false;
        wp_update_post(['ID' => $bookingId, 'post_status' => 'pb_completed']);
        return true;
    }

    /**
     * Compute duration in hours, snapped to 30-min slots.
     */
    public static function diff_hours(string $startAt, string $endAt): float
    {
        $diff = strtotime($endAt) - strtotime($startAt);
        $slots = (int) round($diff / 1800); // 30-min slots
        return $slots / 2;
    }

    private static function release_lock(int $bookingId): void
    {
        $roomId = (int) get_post_meta($bookingId, 'room_id', true);
        $start  = get_post_meta($bookingId, 'start_at', true);
        $end    = get_post_meta($bookingId, 'end_at', true);
        if ($roomId && $start && $end) {
            Slot_Lock::release($roomId, $start, $end);
        }
    }
}