<?php
if (!defined('ABSPATH')) exit;
$q = new \WP_Query(['post_type' => \PianoBooking\CPT::PACKAGE, 'post_status' => 'publish', 'posts_per_page' => -1]);
?>
<div class="pb-packages">
  <?php while ($q->have_posts()): $q->the_post(); $id = get_the_ID();
        $h = get_post_meta($id, 'hours_included', true);
        $p = get_post_meta($id, 'price_hkd', true);
        $d = get_post_meta($id, 'validity_days', true); ?>
    <div class="pb-package-card">
      <h3><?= esc_html(get_the_title()) ?></h3>
      <p><strong><?= esc_html($h) ?> <?= esc_html__('hours', 'piano-booking') ?></strong> · <?= esc_html($d) ?> <?= esc_html__('days', 'piano-booking') ?></p>
      <p class="pb-price">HK$ <?= esc_html($p) ?></p>
      <a class="pb-btn" href="<?= esc_url(add_query_arg(['pb_pkg' => $id], home_url('/packages'))) ?>"><?= esc_html__('Buy', 'piano-booking') ?></a>
    </div>
  <?php endwhile; wp_reset_postdata(); ?>
</div>