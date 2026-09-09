<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Credits_Engine
{
    /**
     * Sum of hours_remaining across all active package purchases for the user.
     */
    public static function balance(int $userId): float
    {
        $total = 0.0;
        foreach (self::active_purchases($userId) as $p) {
            $total += (float) get_post_meta($p->ID, 'hours_remaining', true);
        }
        return $total;
    }

    public static function has_enough(int $userId, float $hours): bool
    {
        return self::balance($userId) >= $hours;
    }

    /**
     * Deduct hours from oldest active package first (FIFO).
     * Returns package_purchase ID used, or WP_Error if insufficient credits.
     */
    public static function deduct(int $userId, float $hours): int|\WP_Error
    {
        if (!self::has_enough($userId, $hours)) {
            return new \WP_Error('pb_not_enough_credits', 'Insufficient package credits');
        }
        $purchases = self::active_purchases($userId); // oldest first
        $used_id = 0;
        foreach ($purchases as $p) {
            $remaining = (float) get_post_meta($p->ID, 'hours_remaining', true);
            if ($remaining <= 0) continue;
            $take = min($remaining, $hours);
            update_post_meta($p->ID, 'hours_remaining', number_format($remaining - $take, 1, '.', ''));
            $hours -= $take;
            if ($used_id === 0) $used_id = $p->ID;
            if ($hours <= 0) break;
        }
        return $used_id ?: new \WP_Error('pb_not_enough_credits', 'No credits deducted');
    }

    /**
     * Refund hours to a package purchase (capped at hours_included).
     */
    public static function refund(int $purchaseId, float $hours): void
    {
        $remaining = (float) get_post_meta($purchaseId, 'hours_remaining', true);
        $included  = (float) get_post_meta($purchaseId, 'hours_included',  true);
        $new = min($included, $remaining + $hours);
        update_post_meta($purchaseId, 'hours_remaining', number_format($new, 1, '.', ''));
    }

    /**
     * Active (not expired, pb_active status) purchases oldest first.
     */
    private static function active_purchases(int $userId): array
    {
        return get_posts([
            'post_type'      => CPT::PACKAGE_PURCHASE,
            'post_status'    => 'pb_active',
            'author'         => $userId,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'meta_query'     => [[
                'key'     => 'expires_at',
                'value'   => gmdate('Y-m-d H:i:s'),
                'compare' => '>',
            ]],
        ]);
    }
}