<?php
$locationId = get_post_meta($post->ID, 'location_id', true);
$capacity   = get_post_meta($post->ID, 'capacity', true);
$equipment  = get_post_meta($post->ID, 'equipment', true) ?: '[]';
$rate       = get_post_meta($post->ID, 'hourly_rate', true);
$active     = get_post_meta($post->ID, 'is_active', true);
$locations  = get_posts(['post_type' => \PianoBooking\CPT::LOCATION, 'post_status' => 'publish', 'posts_per_page' => -1]);
?>
<table class="form-table">
  <tr><th><label><?= esc_html__('Location', 'piano-booking') ?></label></th>
      <td><select name="pb_room_location"><?php foreach ($locations as $loc) echo '<option value="' . esc_attr($loc->ID) . '"' . selected($locationId, $loc->ID, false) . '>' . esc_html($loc->post_title) . '</option>'; ?></select></td></tr>
  <tr><th><label><?= esc_html__('Capacity', 'piano-booking') ?></label></th><td><input name="pb_room_capacity" type="number" value="<?= esc_attr($capacity) ?>" min="1"></td></tr>
  <tr><th><label><?= esc_html__('Equipment (JSON array)', 'piano-booking') ?></label></th><td><input name="pb_room_equipment" value="<?= esc_attr($equipment) ?>" class="regular-text" placeholder='["upright","grand"]'></td></tr>
  <tr><th><label><?= esc_html__('Hourly rate (HKD)', 'piano-booking') ?></label></th><td><input name="pb_room_rate" value="<?= esc_attr($rate) ?>"></td></tr>
  <tr><th><label><?= esc_html__('Active', 'piano-booking') ?></label></th><td><label><input type="checkbox" name="pb_room_active" value="1" <?= checked($active, 1, false) ?>> <?= esc_html__('Available for booking', 'piano-booking') ?></label></td></tr>
</table>