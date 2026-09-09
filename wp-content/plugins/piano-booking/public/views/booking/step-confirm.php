<?php
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) {
    $redirect = get_permalink();
    // Preserve booking context so login/register round-trip keeps the user on the same step
    $ctx = [
        'pb_room'       => (int) ($_GET['pb_room'] ?? 0),
        'pb_date'       => sanitize_text_field($_GET['pb_date'] ?? ''),
        'pb_start_time' => sanitize_text_field($_GET['pb_start_time'] ?? ''),
        'pb_end_time'   => sanitize_text_field($_GET['pb_end_time'] ?? ''),
    ];
    $register_url = add_query_arg($ctx + ['pb_step' => 'register'], home_url('/booking'));
    $login_url    = wp_login_url(add_query_arg($ctx + ['pb_step' => 'confirm'], home_url('/booking')));

    echo '<p>' . esc_html__('Please log in or register to complete your booking.', 'piano-booking') . '</p>';
    echo '<p class="pb-auth-actions">';
    echo '<a class="pb-btn pb-btn-primary" href="' . esc_url($login_url) . '">' . esc_html__('Login', 'piano-booking') . '</a>';
    if (get_option('users_can_register')) {
        echo ' <a class="pb-btn pb-btn-outline" href="' . esc_url($register_url) . '">' . esc_html__('Register', 'piano-booking') . '</a>';
    }
    echo '</p>';
    return;
}
$roomId = (int) ($_GET['pb_room'] ?? 0);
$date   = sanitize_text_field($_GET['pb_date'] ?? '');
$start  = sanitize_text_field($_GET['pb_start_time'] ?? '');
$end    = sanitize_text_field($_GET['pb_end_time'] ?? '');
$room   = get_post($roomId);
$rate   = (float) get_post_meta($roomId, 'hourly_rate', true);
$hours  = (strtotime($end) - strtotime($start)) / 3600;
$amount = number_format($hours * $rate, 2, '.', '');
?>
<form id="pb-confirm-form" class="pb-booking-form">
  <h2><?= esc_html__('Step 4: Confirm & pay', 'piano-booking') ?></h2>
  <ul>
    <li><strong><?= esc_html__('Room', 'piano-booking') ?>:</strong> <?= esc_html($room->post_title) ?></li>
    <li><strong><?= esc_html__('Date', 'piano-booking') ?>:</strong> <?= esc_html($date) ?></li>
    <li><strong><?= esc_html__('Time', 'piano-booking') ?>:</strong> <?= esc_html("$start – $end") ?></li>
    <li><strong><?= esc_html__('Amount', 'piano-booking') ?>:</strong> HK$ <?= esc_html($amount) ?></li>
  </ul>
  <label><input type="radio" name="pb_paid_via" value="qr" checked> <?= esc_html__('Pay via PayMe / FPS', 'piano-booking') ?></label>
  <input type="text" name="pb_name" placeholder="<?= esc_attr__('Name', 'piano-booking') ?>" required>
  <input type="tel" name="pb_phone" placeholder="<?= esc_attr__('Phone (e.g. 9123 4567)', 'piano-booking') ?>" required>
  <input type="email" name="pb_email" placeholder="<?= esc_attr__('Email', 'piano-booking') ?>" required value="<?= esc_attr(wp_get_current_user()->user_email) ?>">
  <button type="submit"><?= esc_html__('Confirm booking', 'piano-booking') ?></button>
</form>