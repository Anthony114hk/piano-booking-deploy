<?php
$address = get_post_meta($post->ID, 'address', true);
$phone   = get_post_meta($post->ID, 'phone', true);
$hours   = get_post_meta($post->ID, 'opening_hours', true) ?: '{}';
$map     = get_post_meta($post->ID, 'map_embed_url', true);
?>
<table class="form-table">
  <tr><th><label><?= esc_html__('Address', 'piano-booking') ?></label></th><td><input name="pb_location_address" value="<?= esc_attr($address) ?>" class="regular-text"></td></tr>
  <tr><th><label><?= esc_html__('Phone', 'piano-booking') ?></label></th><td><input name="pb_location_phone" value="<?= esc_attr($phone) ?>"></td></tr>
  <tr><th><label><?= esc_html__('Opening hours (JSON)', 'piano-booking') ?></label></th><td><textarea name="pb_location_hours" rows="4" class="large-text"><?= esc_textarea($hours) ?></textarea></td></tr>
  <tr><th><label><?= esc_html__('Map embed URL', 'piano-booking') ?></label></th><td><input name="pb_location_map" value="<?= esc_url($map) ?>" class="regular-text"></td></tr>
</table>