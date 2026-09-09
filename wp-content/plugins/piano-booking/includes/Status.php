<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Status
{
    public const BOOKING_STATUSES = [
        'pb_pending'   => 'Pending Payment',
        'pb_paid'      => 'Paid',
        'pb_completed' => 'Completed',
        'pb_cancelled' => 'Cancelled',
    ];

    public const PURCHASE_STATUSES = [
        'pb_pending_payment' => 'Pending Payment',
        'pb_active'          => 'Active',
        'pb_expired'         => 'Expired',
        'pb_cancelled'       => 'Cancelled',
    ];

    public static function register_all(): void
    {
        foreach (self::BOOKING_STATUSES as $slug => $label) {
            register_post_status($slug, [
                'label'                     => $label,
                'public'                    => false,
                'internal'                  => true,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    $label . ' (%s)',
                    $label . ' (%s)',
                    'piano-booking'
                ),
            ]);
        }

        foreach (self::PURCHASE_STATUSES as $slug => $label) {
            register_post_status($slug, [
                'label'                     => $label,
                'public'                    => false,
                'internal'                  => true,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    $label . ' (%s)',
                    $label . ' (%s)',
                    'piano-booking'
                ),
            ]);
        }
    }
}