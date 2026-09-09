<?php
if (!defined('ABSPATH')) exit;
$bookingId = (int) ($_GET['id'] ?? 0);
$inst = $bookingId ? \PianoBooking\Payment::instructions($bookingId) : null;
if (!$inst) { echo '<p>' . esc_html__('Booking not found.', 'piano-booking') . '</p>'; return; }
?>
<div class="pb-booking-done">
  <h2><?= esc_html__('Booking confirmed (pending payment)', 'piano-booking') ?> ✅</h2>
  <p><strong><?= esc_html__('Reference', 'piano-booking') ?>:</strong> <code><?= esc_html($inst['ref']) ?></code></p>
  <p><strong><?= esc_html__('Amount', 'piano-booking') ?>:</strong> HK$ <?= esc_html($inst['amount']) ?></p>
  <h3><?= esc_html__('Pay via', 'piano-booking') ?>:</h3>
  <ul>
    <li>PayMe: <a href="<?= esc_url($inst['payme_url']) ?>" target="_blank"><?= esc_html($inst['payme_url']) ?></a></li>
    <li>FPS ID: <code><?= esc_html($inst['fps_id']) ?></code></li>
    <li>WeChat: <code><?= esc_html($inst['wechat_id']) ?></code></li>
  </ul>
  <p><?= esc_html__('After paying, please', 'piano-booking') ?> <a href="<?= esc_url($inst['upload_url']) ?>"><?= esc_html__('upload your payment proof', 'piano-booking') ?></a> <?= esc_html__('or message us on WhatsApp 9234-5678 with your reference.', 'piano-booking') ?></p>
</div>