<?php
if (!defined('ABSPATH')) exit;
$bookingId = (int) ($atts['booking_id'] ?? $_GET['booking_id'] ?? 0);
?>
<form id="pb-proof-form" enctype="multipart/form-data">
  <input type="hidden" name="booking_id" value="<?= esc_attr($bookingId) ?>">
  <label><?= esc_html__('Payment reference', 'piano-booking') ?> <input name="pb_reference" placeholder="<?= esc_attr__('e.g. BK-XXX-0001', 'piano-booking') ?>"></label>
  <label><?= esc_html__('Screenshot', 'piano-booking') ?> <input type="file" name="pb_proof" accept="image/png,image/jpeg"></label>
  <button type="submit"><?= esc_html__('Upload', 'piano-booking') ?></button>
</form>
<script>
document.getElementById('pb-proof-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var f = new FormData(this);
  f.append('action', 'pb_upload_proof');
  f.append('nonce', PB_AJAX.nonce);
  fetch(PB_AJAX.url, { method: 'POST', body: f })
    .then(function(r){ return r.json(); })
    .then(function(r){ alert(r.success ? 'Uploaded ✓' : r.data); });
});
</script>