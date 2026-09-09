<?php
namespace PianoBooking\Admin;

use PianoBooking\CPT;
if (!defined('ABSPATH')) exit;

class Reports
{
    public static function register(): void {}

    public static function render(): void
    {
        $view = $_GET['view'] ?? 'daily';
        $data = match ($view) {
            'monthly' => self::monthly(),
            'utilization' => self::utilization(),
            default => self::daily(),
        };
        echo '<div class="wrap"><h1>' . esc_html__('Reports', 'piano-booking') . ' — ' . esc_html(ucfirst($view)) . '</h1>';
        echo '<nav class="nav-tab-wrapper">';
        foreach (['daily' => 'Daily', 'monthly' => 'Monthly', 'utilization' => 'Utilization'] as $slug => $label) {
            $cls = $view === $slug ? 'nav-tab nav-tab-active' : 'nav-tab';
            echo '<a class="' . esc_attr($cls) . '" href="?page=pb-reports&view=' . esc_attr($slug) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
        echo '<table class="widefat"><thead><tr>';
        if ($data) foreach (array_keys($data[0]) as $h) echo '<th>' . esc_html($h) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($data as $r) {
            echo '<tr>';
            foreach ($r as $k => $v) {
                echo '<td>' . esc_html((string) (is_array($v) ? wp_json_encode($v) : $v)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function daily(): array
    {
        $rows = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', strtotime("-$i days"));
            $q = new \WP_Query([
                'post_type'      => CPT::BOOKING,
                'post_status'    => 'pb_paid',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'date_query'     => [['after' => $day . ' 00:00:00', 'before' => $day . ' 23:59:59', 'inclusive' => true]],
            ]);
            $sum = 0.0; foreach ($q->posts as $bid) $sum += (float) get_post_meta($bid, 'amount_hkd', true);
            $rows[] = ['date' => $day, 'bookings' => count($q->posts), 'revenue' => 'HK$ ' . number_format($sum, 2)];
        }
        return $rows;
    }

    public static function monthly(): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = gmdate('Y-m', strtotime("-$i months"));
            $sum = (float) $GLOBALS['wpdb']->get_var(
                $GLOBALS['wpdb']->prepare(
                    "SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) FROM {$GLOBALS['wpdb']->postmeta} pm
                     INNER JOIN {$GLOBALS['wpdb']->posts} p ON p.ID = pm.post_id
                     WHERE p.post_type=%s AND p.post_status=%s AND pm.meta_key=%s
                       AND DATE_FORMAT(p.post_date, '%%Y-%%m')=%s",
                    CPT::BOOKING, 'pb_paid', 'amount_hkd', $m
                )
            );
            $rows[] = ['month' => $m, 'revenue' => 'HK$ ' . number_format($sum ?: 0.0, 2)];
        }
        return $rows;
    }

    public static function utilization(): array
    {
        $rows = [];
        $rooms = get_posts(['post_type' => CPT::ROOM, 'post_status' => 'publish', 'posts_per_page' => -1]);
        foreach ($rooms as $r) {
            $q = new \WP_Query([
                'post_type'      => CPT::BOOKING,
                'post_status'    => ['pb_paid', 'pb_completed'],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'date_query'     => [['after' => '7 days ago']],
                'meta_query'     => [['key' => 'room_id', 'value' => $r->ID, 'compare' => '=']],
            ]);
            $hours = 0.0;
            foreach ($q->posts as $bid) {
                $hours += (strtotime(get_post_meta($bid, 'end_at', true)) - strtotime(get_post_meta($bid, 'start_at', true))) / 3600;
            }
            $rows[] = ['room' => $r->post_title, 'booked_hours' => round($hours, 1), 'utilization_pct' => round($hours / 98 * 100, 1) . '%'];
        }
        return $rows;
    }
}