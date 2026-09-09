<?php
$hours    = get_post_meta($post->ID, 'hours_included', true);
$price    = get_post_meta($post->ID, 'price_hkd', true);
$days     = get_post_meta($post->ID, 'validity_days', true);
$active   = get_post_meta($post->ID, 'is_active', true);
?>
<table class="form-table">
  <tr><th><label><?= esc_html__('Hours included', 'piano-booking') ?></label></th><td><input name="pb_pkg_hours" value="<?= esc_attr($hours) ?>" placeholder="12.0"></td></tr>
  <tr><th><label><?= esc_html__('Price (HKD)', 'piano-booking') ?></label></th><td><input name="pb_pkg_price" value="<?= esc_attr($price) ?>" placeholder="650.00"></td></tr>
  <tr><th><label><?= esc_html__('Validity (days)', 'piano-booking') ?></label></th><td><input name="pb_pkg_days" type="number" value="<?= esc_attr($days) ?>" min="1"></td></tr>
  <tr><th><label><?= esc_html__('Active', 'piano-booking') ?></label></th><td><label><input type="checkbox" name="pb_pkg_active" value="1" <?= checked($active, 1, false) ?>> <?= esc_html__('Available for purchase', 'piano-booking') ?></label></td></tr>
</table>