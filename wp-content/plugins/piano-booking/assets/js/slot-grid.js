(function ($) {
  $(function () {
    var grid = $('#pb-slot-grid');
    if (!grid.length) return;

    var roomId = grid.data('room-id');
    var date = grid.data('date');
    var selected = [];
    // Minimum booking is 1 hour = 2 × 30-min slots.
    // Server-side enforces this in Booking_Service::MIN_DURATION_HOURS;
    // client-side just disables the Next button and shows a hint.
    var MIN_SLOTS = 2;

    $.post(PB_AJAX.url, { action: 'pb_get_availability', nonce: PB_AJAX.nonce, room_id: roomId, date: date }, function (r) {
      if (!r.success) { grid.html('<p>Error loading slots.</p>'); return; }
      renderSlots(r.data.slots);
    });

    function renderSlots(slots) {
      grid.empty().append('<div class="pb-slot-grid-wrap"></div>');
      var wrap = grid.find('.pb-slot-grid-wrap');
      Object.keys(slots).forEach(function (t) {
        var btn = $('<button type="button" class="pb-slot"></button>').text(t);
        if (!slots[t]) btn.prop('disabled', true).addClass('pb-unavailable');
        btn.on('click', function () { toggle(t, btn); });
        wrap.append(btn);
      });
      grid.append('<p class="pb-slot-hint">最少要揀 1 個鐘 (2 個 slot)。已選: <span class="pb-selected-count">0</span> 個 slot。</p>');
      updateNextButton();
    }

    function toggle(t, btn) {
      var i = selected.indexOf(t);
      if (i >= 0) { selected.splice(i, 1); btn.removeClass('pb-selected'); }
      else { selected.push(t); selected.sort(); btn.addClass('pb-selected'); }
      grid.find('.pb-selected-count').text(selected.length);
      updateNextButton();
    }

    function updateNextButton() {
      var ok = selected.length >= MIN_SLOTS;
      $('#pb-to-confirm').prop('disabled', !ok);
      if (!ok && selected.length > 0) {
        grid.find('.pb-slot-hint').addClass('pb-hint-warn');
      } else {
        grid.find('.pb-slot-hint').removeClass('pb-hint-warn');
      }
    }

    function addMinutes(hhmm, m) {
      var p = hhmm.split(':').map(Number);
      var d = new Date(2000, 0, 1, p[0], p[1] + m);
      return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    $('#pb-to-confirm').on('click', function () {
      if (selected.length < MIN_SLOTS) return;
      var url = new URL(window.location.href);
      url.searchParams.set('pb_step', 'confirm');
      url.searchParams.set('pb_start_time', selected[0]);
      url.searchParams.set('pb_end_time', addMinutes(selected[selected.length - 1], 30));
      url.searchParams.set('pb_date', date);
      url.searchParams.set('pb_room', roomId);
      window.location.href = url.toString();
    });
  });
})(jQuery);