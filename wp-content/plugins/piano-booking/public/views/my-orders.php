<?php
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in()) {
    echo '<p>' . wp_loginout(get_permalink(), false) . '</p>';
    return;
}
$balance = \PianoBooking\Credits_Engine::balance(get_current_user_id());
if ($balance > 0) {
    echo '<p><strong>' . esc_html__('Your package credits', 'piano-booking') . ':</strong> '
        . esc_html(number_format($balance, 1)) . ' ' . esc_html__('hours remaining', 'piano-booking') . '</p>';
}
$q = new \WP_Query([
    'post_type'      => \PianoBooking\CPT::BOOKING,
    'author'         => get_current_user_id(),
    'post_status'    => array_keys(\PianoBooking\Status::BOOKING_STATUSES),
    'posts_per_page' => 50,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>
<table class="pb-my-orders">
  <thead><tr><th><?= esc_html__('Ref', 'piano-booking') ?></th><th><?= esc_html__('When', 'piano-booking') ?></th><th><?= esc_html__('Room', 'piano-booking') ?></th><th><?= esc_html__('Status', 'piano-booking') ?></th><th><?= esc_html__('Amount', 'piano-booking') ?></th></tr></thead>
  <tbody>
    <?php while ($q->have_posts()): $q->the_post(); $id = get_the_ID(); ?>
      <tr>
        <td><code><?= esc_html(get_the_title()) ?></code></td>
        <td><?= esc_html(get_post_meta($id, 'start_at', true)) ?></td>
        <td><?= esc_html(get_the_title((int) get_post_meta($id, 'room_id', true))) ?></td>
        <td><?= esc_html(get_post_status_object(get_post_status())->label) ?></td>
        <td>HK$ <?= esc_html(get_post_meta($id, 'amount_hkd', true)) ?></td>
      </tr>
    <?php endwhile; wp_reset_postdata(); ?>
  </tbody>
</table>