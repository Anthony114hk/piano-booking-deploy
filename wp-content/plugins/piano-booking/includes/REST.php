<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

/**
 * REST endpoints. Stub — full implementation in Task 3.2 (slot grid AJAX)
 * and Task 3.4 (proof upload).
 */
class REST
{
    public static function availability(): void
    {
        check_ajax_referer('pb_public', 'nonce');
        $roomId = (int) ($_POST['room_id'] ?? 0);
        $date   = sanitize_text_field($_POST['date'] ?? '');
        if (!$roomId || !$date) wp_send_json_error('Missing params');
        $slots = Availability::for_room_and_date($roomId, $date);
        wp_send_json_success(['slots' => $slots, 'date' => $date, 'room_id' => $roomId]);
    }

    public static function create_booking(): void
    {
        check_ajax_referer('pb_public', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Login required');
        $result = Booking_Service::create_pending([
            'user_id'  => get_current_user_id(),
            'room_id'  => (int) ($_POST['room_id'] ?? 0),
            'start_at' => sanitize_text_field($_POST['start_at'] ?? ''),
            'end_at'   => sanitize_text_field($_POST['end_at'] ?? ''),
            'paid_via' => sanitize_text_field($_POST['paid_via'] ?? 'qr'),
            'name'     => sanitize_text_field($_POST['name'] ?? ''),
            'phone'    => sanitize_text_field($_POST['phone'] ?? ''),
            'email'    => sanitize_email($_POST['email'] ?? ''),
        ]);
        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
        wp_send_json_success(['booking_id' => $result, 'redirect' => add_query_arg(['pb_step' => 'done', 'id' => $result], home_url('/booking'))]);
    }

    public static function upload_proof(): void
    {
        check_ajax_referer('pb_public', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Login required');
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $booking = get_post($bookingId);
        if (!$booking || (int) $booking->post_author !== get_current_user_id()) {
            wp_send_json_error('Not your booking');
        }
        $attachmentId = 0;
        if (!empty($_FILES['pb_proof'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $attachmentId = media_handle_upload('pb_proof', 0);
        }
        $ref = sanitize_text_field($_POST['pb_reference'] ?? '');
        Payment::attach_proof($bookingId, is_wp_error($attachmentId) ? 0 : $attachmentId, $ref);
        wp_send_json_success('Proof uploaded');
    }

    public static function create_package_purchase(): void
    {
        check_ajax_referer('pb_public', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Login required');
        $pkgId = (int) ($_POST['pb_pkg_id'] ?? 0);
        $pkg = get_post($pkgId);
        if (!$pkg || $pkg->post_type !== CPT::PACKAGE) wp_send_json_error('Bad package');

        $id = wp_insert_post([
            'post_type'   => CPT::PACKAGE_PURCHASE,
            'post_status' => 'pb_pending_payment',
            'post_author' => get_current_user_id(),
            'post_title'  => Ref_Generator::next(CPT::PACKAGE_PURCHASE),
        ]);
        update_post_meta($id, 'package_id',      $pkgId);
        update_post_meta($id, 'hours_included',  get_post_meta($pkgId, 'hours_included', true));
        update_post_meta($id, 'hours_remaining', get_post_meta($pkgId, 'hours_included', true));
        update_post_meta($id, 'amount_hkd',      get_post_meta($pkgId, 'price_hkd', true));

        wp_send_json_success(['purchase_id' => $id, 'redirect' => add_query_arg(['pb_step' => 'done', 'id' => $id], home_url('/packages'))]);
    }

    /**
     * Public: register a brand-new user, log them in immediately, and
     * redirect back to step-confirm with the booking context preserved.
     *
     * Requires users_can_register = 1. No email is sent — the user picks
     * their own password and is auto-logged-in via wp_set_auth_cookie.
     */
    public static function register_and_login(): void
    {
        check_ajax_referer('pb_public', 'nonce');
        if (!get_option('users_can_register')) {
            wp_send_json_error(__('Self-registration is disabled.', 'piano-booking'));
        }
        if (is_user_logged_in()) {
            wp_send_json_error(__('You are already logged in.', 'piano-booking'));
        }

        $username = sanitize_user($_POST['pb_username'] ?? '');
        $email    = sanitize_email($_POST['pb_email'] ?? '');
        $password = (string) ($_POST['pb_password'] ?? '');

        if ($username === '' || !validate_username($username)) {
            wp_send_json_error(__('Invalid username. Use letters, numbers, underscore, dot, or dash.', 'piano-booking'));
        }
        if (username_exists($username)) {
            wp_send_json_error(__('That username is already taken. Please pick another.', 'piano-booking'));
        }
        if ($email === '' || !is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'piano-booking'));
        }
        if (email_exists($email)) {
            wp_send_json_error(__('That email is already registered. Try logging in instead.', 'piano-booking'));
        }
        if (strlen($password) < 8) {
            wp_send_json_error(__('Password must be at least 8 characters.', 'piano-booking'));
        }

        $userId = wp_create_user($username, $password, $email);
        if (is_wp_error($userId)) {
            wp_send_json_error($userId->get_error_message());
        }

        // Auto-login: set auth cookie immediately
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId, true);

        // Build redirect URL back to step-confirm with booking context preserved
        $redirect_args = ['pb_step' => 'confirm'];
        foreach (['pb_room', 'pb_date', 'pb_start_time', 'pb_end_time'] as $k) {
            if (!empty($_POST[$k])) {
                $redirect_args[$k] = sanitize_text_field($_POST[$k]);
            }
        }
        wp_send_json_success(['user_id' => $userId, 'redirect' => add_query_arg($redirect_args, home_url('/booking'))]);
    }

    /**
     * Admin: mark booking as paid.
     */
    public static function admin_verify(): void
    {
        check_ajax_referer('pb_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        $id = (int) ($_POST['booking_id'] ?? 0);
        if (!$id) wp_send_json_error('Missing booking_id');
        $ok = Booking_Service::mark_paid($id, get_current_user_id());
        if (!$ok) wp_send_json_error('Booking not in pending status');
        wp_send_json_success('Verified');
    }

    /**
     * Admin: reject booking (cancel + record reason).
     */
    public static function admin_reject(): void
    {
        check_ajax_referer('pb_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        $id = (int) ($_POST['booking_id'] ?? 0);
        if (!$id) wp_send_json_error('Missing booking_id');
        $reason = sanitize_text_field($_POST['reason'] ?? 'payment not received');
        $ok = Booking_Service::mark_rejected($id, get_current_user_id(), $reason);
        if (!$ok) wp_send_json_error('Booking not in pending status');
        wp_send_json_success('Rejected');
    }

    /**
     * Admin: cancel a booking that is already paid or completed.
     * Refunds credits if the booking was paid via package, releases the
     * slot lock, and stores the cancellation reason.
     */
    public static function admin_cancel(): void
    {
        check_ajax_referer('pb_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        $id = (int) ($_POST['booking_id'] ?? 0);
        if (!$id) wp_send_json_error('Missing booking_id');
        $reason = sanitize_text_field($_POST['reason'] ?? 'cancelled by staff');
        $ok = Booking_Service::mark_cancelled($id, $reason);
        if (!$ok) wp_send_json_error('Booking cannot be cancelled from its current status');
        wp_send_json_success('Cancelled');
    }

    /**
     * Admin: activate a pending package purchase (staff confirms payment).
     */
    public static function admin_activate_package(): void
    {
        check_ajax_referer('pb_admin', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');
        $id = (int) ($_POST['purchase_id'] ?? 0);
        if (!$id) wp_send_json_error('Missing purchase_id');
        $post = get_post($id);
        if (!$post || $post->post_type !== CPT::PACKAGE_PURCHASE) wp_send_json_error('Not a package purchase');
        wp_update_post(['ID' => $id, 'post_status' => 'pb_active']);
        update_post_meta($id, 'activated_at', current_time('mysql', true));
        update_post_meta($id, 'activated_by', get_current_user_id());
        wp_send_json_success('Activated');
    }
}