<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Availability
{
    private const OPEN_H  = 9;
    private const CLOSE_H = 23;

    /**
     * Returns availability map for a room on a date.
     * Format: ['HH:MM' => true (free) | false (booked/held)]
     *
     * @param int    $roomId
     * @param string $dateYmd  'Y-m-d' (interpreted as HKT date)
     * @return array<string,bool>
     */
    public static function for_room_and_date(int $roomId, string $dateYmd): array
    {
        if (!get_post_meta($roomId, 'is_active', true)) return [];

        $slots = self::generate_slot_keys();
        $bookings = self::query_bookings_for_room_on_date($roomId, $dateYmd);

        foreach ($slots as $key => &$available) {
            foreach ($bookings as $b) {
                if (self::slot_overlaps_booking($key, $b)) {
                    $available = false;
                    break;
                }
            }
            if (self::is_held($roomId, $dateYmd, $key)) $available = false;
        }
        return $slots;
    }

    /**
     * Check whether a datetime falls within operating hours.
     */
    public static function is_open(int $roomId, string $datetimeYmdHms): bool
    {
        $ts = strtotime($datetimeYmdHms . ' HKT');
        $h = (int) date('G', $ts);
        return $h >= self::OPEN_H && $h < self::CLOSE_H;
    }

    /**
     * Generate all HH:MM slot keys for an operating day.
     * Pure function, easy to test without WP.
     */
    public static function generate_slot_keys(): array
    {
        $keys = [];
        for ($h = self::OPEN_H; $h < self::CLOSE_H; $h++) {
            foreach (['00', '30'] as $m) {
                $keys[sprintf('%02d:%s', $h, $m)] = true;
            }
        }
        return $keys;
    }

    /**
     * Pure function — does a slot key overlap a booking range?
     * Compares HH:MM strings; assumes same-day booking.
     */
    public static function slot_overlaps_booking(string $timeHm, array $booking): bool
    {
        $bStartHm = substr($booking['start'], 11, 5);
        $bEndHm   = substr($booking['end'],   11, 5);
        return $timeHm >= $bStartHm && $timeHm < $bEndHm;
    }

    /**
     * Query bookings for a room on a given date.
     */
    private static function query_bookings_for_room_on_date(int $roomId, string $dateYmd): array
    {
        $start = "{$dateYmd} 00:00:00";
        $end   = "{$dateYmd} 23:59:59";

        $q = new \WP_Query([
            'post_type'      => CPT::BOOKING,
            'post_status'    => ['pb_pending', 'pb_paid', 'pb_completed'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => 'room_id',  'value' => $roomId, 'compare' => '='],
                ['key' => 'start_at', 'value' => [$start, $end], 'compare' => 'BETWEEN'],
            ],
        ]);

        $out = [];
        foreach ($q->posts as $bid) {
            $out[] = [
                'start' => get_post_meta($bid, 'start_at', true),
                'end'   => get_post_meta($bid, 'end_at', true),
            ];
        }
        return $out;
    }

    /**
     * Check if any transient lock for this room covers this slot.
     * Transient name format: pb_slot_<roomId>_<dateYmd> <HH:MM:SS>_<dateYmd> <HH:MM:SS>
     * Two earlier bugs fixed here:
     *   (a) no date filter — locks for other dates leaked in
     *   (b) substring match on " HH:MM:" also matched the END time of a lock
     *       whose start time was a different slot, blocking the slot right
     *       after the lock (e.g. lock 16:00–17:00 incorrectly blocked 17:00).
     */
    private static function is_held(int $roomId, string $dateYmd, string $timeHm): bool
    {
        global $wpdb;
        $prefix = '_transient_pb_slot_' . $roomId . '_' . $dateYmd . ' ';
        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like($prefix) . '%'
            )
        );
        $afterPrefix = 'pb_slot_' . $roomId . '_' . $dateYmd;
        foreach ($rows as $opt) {
            $name = str_replace('_transient_', '', $opt);
            // After stripping the room/date prefix, what's left starts with " HH:MM:..."
            // — that's the START time of the lock. We only match the START, never the
            // END (which would falsely block the slot immediately after the lock ends).
            $after = substr($name, strlen($afterPrefix));
            if (str_starts_with($after, ' ' . $timeHm . ':')) return true;
        }
        return false;
    }
}