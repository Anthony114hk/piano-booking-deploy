<?php
if (!defined('ABSPATH')) exit;
$roomId = (int) ($_GET['pb_room'] ?? 0);
$date   = sanitize_text_field($_GET['pb_date'] ?? date('Y-m-d'));
?>
<form id="pb-booking-form" class="pb-booking-form">
  <input type="hidden" name="pb_step" value="confirm">
  <input type="hidden" name="pb_room" value="<?= esc_attr($roomId) ?>">
  <input type="hidden" name="pb_date" value="<?= esc_attr($date) ?>">
  <h2><?= esc_html__('Step 3: Pick time slots', 'piano-booking') ?></h2>
  <div id="pb-slot-grid" data-room-id="<?= esc_attr($roomId) ?>" data-date="<?= esc_attr($date) ?>">
    <div class="pb-loading"><?= esc_html__('Loading...', 'piano-booking') ?></div>
  </div>
  <button type="button" id="pb-to-confirm" disabled><?= esc_html__('Next: confirm', 'piano-booking') ?></button>
</form>