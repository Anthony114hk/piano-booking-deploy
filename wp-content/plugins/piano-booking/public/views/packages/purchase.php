<?php
if (!defined('ABSPATH')) exit;
$pkgId = (int) ($atts['id'] ?? $_GET['pb_pkg'] ?? 0);
$pkg   = get_post($pkgId);
if (!$pkg || $pkg->post_type !== \PianoBooking\CPT::PACKAGE) {
    echo '<p>' . esc_html__('Package not found.', 'piano-booking') . '</p>'; return;
}
if (!is_user_logged_in()) {
    echo '<a class="pb-btn" href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Login to buy', 'piano-booking') . '</a>';
    return;
}
$price = get_post_meta($pkgId, 'price_hkd', true);
?>
<form id="pb-pkg-form">
  <input type="hidden" name="pb_pkg_id" value="<?= esc_attr($pkgId) ?>">
  <h2><?= esc_html($pkg->post_title) ?></h2>
  <p><?= esc_html__('Total', 'piano-booking') ?>: HK$ <?= esc_html($price) ?></p>
  <button type="submit"><?= esc_html__('Buy now', 'piano-booking') ?></button>
</form>
<script>
document.getElementById('pb-pkg-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var fd = new FormData(this);
  fd.append('action', 'pb_create_package_purchase');
  fd.append('nonce', PB_AJAX.nonce);
  fetch(PB_AJAX.url, { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(r){
      if (!r.success) return alert(r.data);
      window.location.href = r.data.redirect;
    });
});
</script>