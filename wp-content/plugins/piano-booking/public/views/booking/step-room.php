<?php
if (!defined('ABSPATH')) exit;
$locationId = (int) ($_GET['pb_loc'] ?? 0);
$date = sanitize_text_field($_GET['pb_date'] ?? date('Y-m-d'));
$rooms = new \WP_Query([
    'post_type' => \PianoBooking\CPT::ROOM,
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => [['key' => 'location_id', 'value' => $locationId, 'compare' => '=']],
]);
?>
<form method="get" class="pb-booking-form">
  <input type="hidden" name="pb_step" value="slot">
  <input type="hidden" name="pb_loc" value="<?= esc_attr($locationId) ?>">
  <input type="hidden" name="pb_date" value="<?= esc_attr($date) ?>">
  <h2><?= esc_html__('Step 2: Select room', 'piano-booking') ?></h2>
  <div class="pb-room-list">
    <?php while ($rooms->have_posts()): $rooms->the_post(); ?>
      <label class="pb-room-card">
        <input type="radio" name="pb_room" value="<?= esc_attr(get_the_ID()) ?>" required>
        <strong><?= esc_html(get_the_title()) ?></strong>
        <span>$<?= esc_html(get_post_meta(get_the_ID(), 'hourly_rate', true)) ?>/hr</span>
      </label>
    <?php endwhile; wp_reset_postdata(); ?>
  </div>
  <button type="submit"><?= esc_html__('Next: choose time', 'piano-booking') ?></button>
</form>