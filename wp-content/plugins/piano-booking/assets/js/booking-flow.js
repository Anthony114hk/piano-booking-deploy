(function ($) {
  $(function () {
    $('#pb-confirm-form').on('submit', function (e) {
      e.preventDefault();
      var f = this;
      var url = new URL(window.location.href);
      $.post(PB_AJAX.url, {
        action: 'pb_create_booking',
        nonce: PB_AJAX.nonce,
        room_id: url.searchParams.get('pb_room'),
        start_at: url.searchParams.get('pb_date') + ' ' + url.searchParams.get('pb_start_time') + ':00',
        end_at: url.searchParams.get('pb_date') + ' ' + url.searchParams.get('pb_end_time') + ':00',
        paid_via: f.pb_paid_via.value,
        name: f.pb_name.value,
        phone: f.pb_phone.value,
        email: f.pb_email.value,
      }, function (r) {
        if (!r.success) { alert(r.data); return; }
        window.location.href = r.data.redirect;
      });
    });
  });
})(jQuery);