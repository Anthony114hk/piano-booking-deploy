<?php
if (!defined('ABSPATH')) exit;
$locations = new \WP_Query(['post_type' => \PianoBooking\CPT::LOCATION, 'post_status' => 'publish', 'posts_per_page' => -1]);
?>
<form method="get" class="pb-booking-form">
  <input type="hidden" name="pb_step" value="room">
  <h2><?= esc_html__('Step 1: Select location', 'piano-booking') ?></h2>
  <select name="pb_loc" required>
    <?php while ($locations->have_posts()): $locations->the_post(); ?>
      <option value="<?= esc_attr(get_the_ID()) ?>"><?= esc_html(get_the_title()) ?></option>
    <?php endwhile; wp_reset_postdata(); ?>
  </select>
  <label><?= esc_html__('Date', 'piano-booking') ?>
    <input type="date" name="pb_date" min="<?= esc_attr(date('Y-m-d')) ?>" max="<?= esc_attr(date('Y-m-d', strtotime('+30 days'))) ?>" required>
  </label>
  <button type="submit"><?= esc_html__('Next: choose room', 'piano-booking') ?></button>
</form>