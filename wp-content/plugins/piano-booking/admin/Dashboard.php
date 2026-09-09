<?php
namespace PianoBooking\Admin;

use PianoBooking\CPT;
if (!defined('ABSPATH')) exit;

class Dashboard
{
    public static function render(): void
    {
        $kpis = [
            'pending_today'    => self::kpi_pending_today(),
            'received_today'   => self::kpi_received_today(),
            'room_hours_today' => self::kpi_room_hours_today(),
            'expiring_soon'    => self::kpi_expiring_packages(),
        ];

        // Month navigation (?pb_month=YYYY-MM)
        $cal_month = sanitize_text_field($_GET['pb_month'] ?? gmdate('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $cal_month)) {
            $cal_month = gmdate('Y-m');
        }
        $cal_prev = date('Y-m', strtotime($cal_month . '-01 -1 month'));
        $cal_next = date('Y-m', strtotime($cal_month . '-01 +1 month'));

        $calendar_cells = self::build_calendar($cal_month);

        include PB_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    private static function kpi_pending_today(): int
    {
        $q = new \WP_Query([
            'post_type'      => CPT::BOOKING,
            'post_status'    => 'pb_pending',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => [['after' => 'today', 'before' => 'tomorrow']],
        ]);
        return count($q->posts);
    }

    private static function kpi_received_today(): float
    {
        $q = new \WP_Query([
            'post_type'      => CPT::BOOKING,
            'post_status'    => 'pb_paid',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => [['after' => 'today', 'before' => 'tomorrow']],
        ]);
        $sum = 0.0;
        foreach ($q->posts as $bid) $sum += (float) get_post_meta($bid, 'amount_hkd', true);
        return $sum;
    }

    private static function kpi_room_hours_today(): float
    {
        $q = new \WP_Query([
            'post_type'      => CPT::BOOKING,
            'post_status'    => ['pb_paid', 'pb_completed'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => [['after' => 'today', 'before' => 'tomorrow']],
        ]);
        $total = 0.0;
        foreach ($q->posts as $bid) {
            $s = get_post_meta($bid, 'start_at', true);
            $e = get_post_meta($bid, 'end_at',   true);
            $total += (strtotime($e) - strtotime($s)) / 3600;
        }
        return $total;
    }

    private static function kpi_expiring_packages(): int
    {
        $q = new \WP_Query([
            'post_type'      => CPT::PACKAGE_PURCHASE,
            'post_status'    => 'pb_active',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [[
                'key'     => 'expires_at',
                'value'   => [gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s', strtotime('+7 days'))],
                'compare' => 'BETWEEN',
            ]],
        ]);
        return count($q->posts);
    }

    /**
     * Build a 6-week × 7-day grid for the given month, with per-day booking counts by status.
     *
     * @return array<int,array{date:string,date_num:int,in_month:bool,is_today:bool,is_past:bool,counts:array<string,int>,total:int}>
     */
    private static function build_calendar(string $ym): array
    {
        $first     = strtotime($ym . '-01');
        $month_len = (int) date('t', $first);
        $today     = gmdate('Y-m-d');

        // Bookings in this month — group by date of start_at (local-tz comparison via UTC strings is OK here)
        $q = new \WP_Query([
            'post_type'      => CPT::BOOKING,
            'post_status'    => ['pb_pending', 'pb_paid', 'pb_completed', 'pb_cancelled'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [[
                'key'     => 'start_at',
                'value'   => [$ym . '-01 00:00:00', date('Y-m-t 23:59:59', $first)],
                'compare' => 'BETWEEN',
            ]],
        ]);
        $by_date = [];
        foreach ($q->posts as $bid) {
            $start = (string) get_post_meta($bid, 'start_at', true);
            if ($start === '') continue;
            $date_key = substr($start, 0, 10);
            $status   = (string) get_post_status($bid);
            $by_date[$date_key][$status] = ($by_date[$date_key][$status] ?? 0) + 1;
        }

        // ISO week: Mon=1..Sun=7. PHP date('N') gives exactly this.
        $first_dow = (int) date('N', $first); // 1..7
        $start_grid = strtotime("-" . ($first_dow - 1) . " days", $first);

        $cells = [];
        for ($i = 0; $i < 42; $i++) {
            $ts       = strtotime("+{$i} days", $start_grid);
            $date_key = date('Y-m-d', $ts);
            $in_month = (int) date('m', $ts) === (int) date('m', $first);
            $counts   = $by_date[$date_key] ?? [];
            $total    = array_sum($counts);
            $cells[]  = [
                'date'     => $date_key,
                'date_num' => (int) date('j', $ts),
                'in_month' => $in_month,
                'is_today' => $date_key === $today,
                'is_past'  => $date_key < $today,
                'counts'   => $counts + ['pb_pending' => 0, 'pb_paid' => 0, 'pb_completed' => 0, 'pb_cancelled' => 0],
                'total'    => $total,
            ];
        }
        return $cells;
    }
}