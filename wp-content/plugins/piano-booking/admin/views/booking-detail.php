<?php /** @var \WP_Post $booking */ ?>
<div class="wrap pb-booking-detail">
  <h1><?= esc_html__('Booking', 'piano-booking') ?> · <code><?= esc_html($booking->post_title) ?></code></h1>
  <div class="pb-two-col">
    <div class="pb-col">
      <h2><?= esc_html__('Details', 'piano-booking') ?></h2>
      <ul>
        <li><strong><?= esc_html__('Status', 'piano-booking') ?>:</strong> <?= esc_html(get_post_status_object($booking->post_status)->label ?? $booking->post_status) ?></li>
        <li><strong><?= esc_html__('Room', 'piano-booking') ?>:</strong> <?= esc_html(get_the_title((int) get_post_meta($booking->ID, 'room_id', true))) ?></li>
        <li><strong><?= esc_html__('Location', 'piano-booking') ?>:</strong> <?= esc_html(get_the_title((int) get_post_meta($booking->ID, 'location_id', true))) ?></li>
        <li><strong><?= esc_html__('Time', 'piano-booking') ?>:</strong> <?= esc_html(get_post_meta($booking->ID, 'start_at', true)) ?> – <?= esc_html(get_post_meta($booking->ID, 'end_at', true)) ?></li>
        <li><strong><?= esc_html__('Amount', 'piano-booking') ?>:</strong> HK$ <?= esc_html(get_post_meta($booking->ID, 'amount_hkd', true)) ?></li>
        <li><strong><?= esc_html__('Customer', 'piano-booking') ?>:</strong> <?= esc_html(get_post_meta($booking->ID, 'customer_name', true)) ?> · <?= esc_html(get_post_meta($booking->ID, 'customer_phone', true)) ?> · <?= esc_html(get_post_meta($booking->ID, 'customer_email', true)) ?></li>
        <li><strong><?= esc_html__('User', 'piano-booking') ?>:</strong> <?= $booking->post_author ? esc_html(get_userdata($booking->post_author)->display_name ?? '—') : '—' ?></li>
      </ul>
    </div>
    <div class="pb-col">
      <h2><?= esc_html__('Payment', 'piano-booking') ?></h2>
      <ul>
        <li><strong><?= esc_html__('Paid via', 'piano-booking') ?>:</strong> <?= esc_html(ucfirst((string) get_post_meta($booking->ID, 'paid_via', true))) ?></li>
        <li><strong><?= esc_html__('Reference', 'piano-booking') ?>:</strong> <?= esc_html((string) (get_post_meta($booking->ID, 'payment_reference', true) ?: '—')) ?></li>
        <li><strong><?= esc_html__('Proof', 'piano-booking') ?>:</strong> <?php $proof = get_post_meta($booking->ID, 'payment_proof_url', true); echo $proof ? '<a href="' . esc_url($proof) . '" target="_blank">' . esc_html__('View', 'piano-booking') . '</a>' : '—'; ?></li>
        <?php if ($booking->post_status === 'pb_pending'): ?>
          <li><button class="button button-primary" id="pb-admin-verify" data-id="<?= esc_attr($booking->ID) ?>"><?= esc_html__('Mark as Paid', 'piano-booking') ?></button>
              <button class="button" id="pb-admin-reject"  data-id="<?= esc_attr($booking->ID) ?>"><?= esc_html__('Reject', 'piano-booking') ?></button></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>