<?php
/**
 * @var array<string,int|float> $kpis
 * @var array<string,array<string,int>> $calendar  // [date => [status => count]]
 * @var string $cal_month  // YYYY-MM
 * @var string $cal_prev   // YYYY-MM
 * @var string $cal_next   // YYYY-MM
 * @var array<int,array{date:string,date_num:int,in_month:bool,is_today:bool,is_past:bool,counts:array<string,int>,total:int}> $calendar_cells
 */
?>
<div class="wrap pb-dashboard">
  <h1><?= esc_html__('Piano Booking Dashboard', 'piano-booking') ?></h1>

  <!-- KPI cards -->
  <div class="pb-kpi-row">
    <div class="pb-kpi">
      <div class="pb-kpi-icon pb-kpi-pending">⏳</div>
      <div class="pb-kpi-body">
        <p class="pb-kpi-label"><?= esc_html__('Pending today', 'piano-booking') ?></p>
        <p class="pb-kpi-value"><?= esc_html(number_format((float) $kpis['pending_today'])) ?></p>
        <p class="pb-kpi-sub"><?= esc_html__('awaiting payment', 'piano-booking') ?></p>
      </div>
    </div>

    <div class="pb-kpi">
      <div class="pb-kpi-icon pb-kpi-received">💰</div>
      <div class="pb-kpi-body">
        <p class="pb-kpi-label"><?= esc_html__('Received today', 'piano-booking') ?></p>
        <p class="pb-kpi-value">HK$ <?= esc_html(number_format((float) $kpis['received_today'], 2)) ?></p>
        <p class="pb-kpi-sub"><?= esc_html__('verified payments', 'piano-booking') ?></p>
      </div>
    </div>

    <div class="pb-kpi">
      <div class="pb-kpi-icon pb-kpi-hours">🎹</div>
      <div class="pb-kpi-body">
        <p class="pb-kpi-label"><?= esc_html__('Room-hours today', 'piano-booking') ?></p>
        <p class="pb-kpi-value"><?= esc_html(number_format((float) $kpis['room_hours_today'], 1)) ?><span class="unit">hrs</span></p>
        <p class="pb-kpi-sub"><?= esc_html__('paid + completed', 'piano-booking') ?></p>
      </div>
    </div>

    <div class="pb-kpi">
      <div class="pb-kpi-icon pb-kpi-expiring">📦</div>
      <div class="pb-kpi-body">
        <p class="pb-kpi-label"><?= esc_html__('Packages expiring 7d', 'piano-booking') ?></p>
        <p class="pb-kpi-value"><?= esc_html(number_format((float) $kpis['expiring_soon'])) ?></p>
        <p class="pb-kpi-sub"><?= esc_html__('active, expires soon', 'piano-booking') ?></p>
      </div>
    </div>
  </div>

  <!-- Calendar -->
  <div class="pb-calendar">
    <div class="pb-cal-header">
      <h2>📅 <?= esc_html__('Bookings Calendar', 'piano-booking') ?></h2>
      <div class="pb-cal-nav">
        <a class="button" href="<?= esc_url(add_query_arg('pb_month', $cal_prev)) ?>">◀</a>
        <span class="month-label"><?= esc_html(date('F Y', strtotime($cal_month . '-01'))) ?></span>
        <a class="button" href="<?= esc_url(add_query_arg('pb_month', $cal_next)) ?>">▶</a>
        <a class="button button-primary" href="<?= esc_url(remove_query_arg('pb_month')) ?>"><?= esc_html__('Today', 'piano-booking') ?></a>
      </div>
    </div>

    <div class="pb-cal-grid">
      <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow): ?>
        <div class="pb-cal-dow"><?= esc_html__($dow, 'piano-booking') ?></div>
      <?php endforeach; ?>

      <?php foreach ($calendar_cells as $cell): ?>
        <?php
          $classes = ['pb-cal-day'];
          if (!$cell['in_month']) $classes[] = 'pb-empty';
          if ($cell['is_today']) $classes[] = 'pb-today';
          if ($cell['is_past'] && $cell['in_month']) $classes[] = 'pb-past';

          $href = $cell['in_month'] && $cell['total'] > 0
            ? admin_url('admin.php?page=pb-bookings&pb_date=' . $cell['date'])
            : '';
        ?>
        <div class="<?= esc_attr(implode(' ', $classes)) ?>"
             <?= $cell['in_month'] ? 'data-date="' . esc_attr($cell['date']) . '"' : '' ?>
             <?= $href ? 'onclick="window.location=\'' . esc_url($href) . '\'" style="cursor:pointer"' : '' ?>>
          <?php if ($cell['in_month']): ?>
            <span class="pb-cal-date"><?= esc_html((string) $cell['date_num']) ?></span>
            <span class="pb-cal-count <?= $cell['total'] === 0 ? 'pb-zero' : '' ?>">
              <?= $cell['total'] > 0 ? esc_html((string) $cell['total']) : '' ?>
            </span>
            <div class="pb-cal-dots">
              <?php foreach ($cell['counts'] as $status => $count):
                if ($count === 0) continue; ?>
                <span class="pb-cal-d-dot pb-<?= esc_attr(str_replace('pb_', '', $status)) ?>"
                      title="<?= esc_attr("$status: $count") ?>"></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="pb-cal-legend">
      <span class="pb-cal-legend-item"><span class="pb-cal-d-dot pb-pending"></span><?= esc_html__('Pending', 'piano-booking') ?></span>
      <span class="pb-cal-legend-item"><span class="pb-cal-d-dot pb-paid"></span><?= esc_html__('Paid', 'piano-booking') ?></span>
      <span class="pb-cal-legend-item"><span class="pb-cal-d-dot pb-completed"></span><?= esc_html__('Completed', 'piano-booking') ?></span>
      <span class="pb-cal-legend-item"><span class="pb-cal-d-dot pb-cancelled"></span><?= esc_html__('Cancelled', 'piano-booking') ?></span>
      <span class="pb-cal-legend-item">💡 <?= esc_html__('Click a day with bookings to view details', 'piano-booking') ?></span>
    </div>
  </div>
</div>