(function ($) {
  $(function () {
    $('#pb-admin-verify, .pb-admin-verify').on('click', function () {
      var id = $(this).data('id');
      $.post(PB_ADMIN.ajax, { action: 'pb_admin_verify', nonce: PB_ADMIN.nonce, booking_id: id }, function (r) {
        if (r && r.success) location.reload(); else alert('Verify failed: ' + (r && r.data ? r.data : 'unknown'));
      });
    });
    $('#pb-admin-reject, .pb-admin-reject').on('click', function () {
      var id = $(this).data('id');
      var reason = prompt('Reason for rejection?', 'payment not received');
      if (!reason) return;
      $.post(PB_ADMIN.ajax, { action: 'pb_admin_reject', nonce: PB_ADMIN.nonce, booking_id: id, reason: reason }, function (r) {
        if (r && r.success) location.reload(); else alert('Reject failed: ' + (r && r.data ? r.data : 'unknown'));
      });
    });
    $('#pb-admin-cancel, .pb-admin-cancel').on('click', function () {
      if (!confirm('Cancel this booking? The slot will be released and package credits (if any) refunded.')) return;
      var id = $(this).data('id');
      var reason = prompt('Reason for cancellation?', 'cancelled by staff');
      if (!reason) return;
      $.post(PB_ADMIN.ajax, { action: 'pb_admin_cancel', nonce: PB_ADMIN.nonce, booking_id: id, reason: reason }, function (r) {
        if (r && r.success) location.reload(); else alert('Cancel failed: ' + (r && r.data ? r.data : 'unknown'));
      });
    });
    $('.pb-admin-activate').on('click', function () {
      var id = $(this).data('id');
      $.post(PB_ADMIN.ajax, { action: 'pb_admin_activate_package', nonce: PB_ADMIN.nonce, purchase_id: id }, function (r) {
        if (r && r.success) location.reload(); else alert('Activate failed: ' + (r && r.data ? r.data : 'unknown'));
      });
    });
  });
})(jQuery);