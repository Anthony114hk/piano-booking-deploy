<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Cron
{
    public const SCHEDULES = [
        'pb_every_10min' => ['interval' => 600,  'display' => 'Every 10 minutes'],
        'pb_every_30min' => ['interval' => 1800, 'display' => 'Every 30 minutes'],
    ];

    public static function register_hooks(): void
    {
        add_filter('cron_schedules', [self::class, 'add_schedules']);
        add_action('pb_send_24h_reminder',  [self::class, 'send_24h_reminders']);
        add_action('pb_expire_pending',     [self::class, 'expire_pending']);
        add_action('pb_package_expiry_warn',[self::class, 'warn_package_expiry']);
        add_action('pb_package_expire',     [self::class, 'expire_packages']);
        add_action('pb_warm_availability_cache', [self::class, 'warm_availability_cache']);
    }

    public static function add_schedules(array $schedules): array
    {
        return array_merge($schedules, self::SCHEDULES);
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled('pb_send_24h_reminder')) wp_schedule_event(time(), 'pb_every_10min', 'pb_send_24h_reminder');
        if (!wp_next_scheduled('pb_expire_pending'))    wp_schedule_event(time(), 'pb_every_30min', 'pb_expire_pending');
        if (!wp_next_scheduled('pb_package_expiry_warn')) wp_schedule_event(time() + 3600, 'daily', 'pb_package_expiry_warn');
        if (!wp_next_scheduled('pb_package_expire'))     wp_schedule_event(time() + 7200, 'daily', 'pb_package_expire');
        if (!wp_next_scheduled('pb_warm_availability_cache')) wp_schedule_event(time() + 10800, 'daily', 'pb_warm_availability_cache');
    }

    public static function unschedule(): void
    {
        foreach (['pb_send_24h_reminder','pb_expire_pending','pb_package_expiry_warn','pb_package_expire','pb_warm_availability_cache'] as $h) {
            $ts = wp_next_scheduled($h); if ($ts) wp_unschedule_event($ts, $h);
        }
    }

    public static function send_24h_reminders(): int
    {
        $start = gmdate('Y-m-d H:i:s', time() + 23 * HOUR_IN_SECONDS);
        $end   = gmdate('Y-m-d H:i:s', time() + 25 * HOUR_IN_SECONDS);
        $q = new \WP_Query([
            'post_type'   => CPT::BOOKING, 'post_status' => 'pb_paid', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query'  => [['key' => 'start_at', 'value' => [$start, $end], 'compare' => 'BETWEEN']],
        ]);
        $count = 0;
        foreach ($q->posts as $bid) {
            Notifications::dispatch('booking_reminder', [
                'to'         => get_post_meta($bid, 'customer_email', true),
                'booking_id' => $bid,
                'ref'        => get_post_field('post_title', $bid),
                'start'      => get_post_meta($bid, 'start_at', true),
                'room'       => get_the_title((int) get_post_meta($bid, 'room_id', true)),
            ]);
            $count++;
        }
        return $count;
    }

    public static function expire_pending(): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - 30 * MINUTE_IN_SECONDS);
        $q = new \WP_Query([
            'post_type'   => CPT::BOOKING, 'post_status' => 'pb_pending', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query'  => [['key' => 'start_at', 'value' => $cutoff, 'compare' => '<']],
        ]);
        $count = 0;
        foreach ($q->posts as $bid) {
            Booking_Service::mark_cancelled($bid, 'auto-cancelled: payment not received');
            Notifications::dispatch('booking_auto_cancelled', [
                'to'         => get_post_meta($bid, 'customer_email', true),
                'booking_id' => $bid,
                'ref'        => get_post_field('post_title', $bid),
            ]);
            $count++;
        }
        return $count;
    }

    public static function warn_package_expiry(): int
    {
        $soon = gmdate('Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS);
        $q = new \WP_Query([
            'post_type'   => CPT::PACKAGE_PURCHASE, 'post_status' => 'pb_active', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query'  => [['key' => 'expires_at', 'value' => $soon, 'compare' => '<']],
        ]);
        $count = 0;
        foreach ($q->posts as $pid) {
            $user = get_userdata((int) get_post_field('post_author', $pid));
            if ($user) {
                Notifications::dispatch('package_expiring_soon', [
                    'to'         => $user->user_email,
                    'purchase_id'=> $pid,
                    'ref'        => get_post_field('post_title', $pid),
                    'remaining'  => get_post_meta($pid, 'hours_remaining', true),
                ]);
            }
            $count++;
        }
        return $count;
    }

    public static function expire_packages(): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $q = new \WP_Query([
            'post_type'   => CPT::PACKAGE_PURCHASE, 'post_status' => 'pb_active', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query'  => [['key' => 'expires_at', 'value' => $now, 'compare' => '<']],
        ]);
        $count = 0;
        foreach ($q->posts as $pid) {
            wp_update_post(['ID' => $pid, 'post_status' => 'pb_expired']);
            $user = get_userdata((int) get_post_field('post_author', $pid));
            if ($user) {
                Notifications::dispatch('package_expired', [
                    'to'         => $user->user_email,
                    'purchase_id'=> $pid,
                    'ref'        => get_post_field('post_title', $pid),
                ]);
            }
            $count++;
        }
        return $count;
    }

    public static function warm_availability_cache(): int
    {
        $rooms = get_posts(['post_type' => CPT::ROOM, 'post_status' => 'publish', 'posts_per_page' => -1]);
        $count = 0;
        for ($d = 0; $d < 7; $d++) {
            $date = gmdate('Y-m-d', strtotime("+$d days"));
            foreach ($rooms as $r) {
                $slots = Availability::for_room_and_date($r->ID, $date);
                wp_cache_set("pb_avail_{$r->ID}_{$date}", $slots, 'pb', DAY_IN_SECONDS);
                $count++;
            }
        }
        return $count;
    }
}