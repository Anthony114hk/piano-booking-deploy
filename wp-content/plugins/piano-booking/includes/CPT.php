<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class CPT
{
    public const LOCATION          = 'pb_location';
    public const ROOM              = 'pb_room';
    public const PACKAGE           = 'pb_package';
    public const BOOKING           = 'pb_booking';
    public const PACKAGE_PURCHASE  = 'pb_package_purchase';

    public static function register_all(): void
    {
        register_post_type(self::LOCATION, [
            'labels' => [
                'name'          => __('Locations', 'piano-booking'),
                'singular_name' => __('Location', 'piano-booking'),
                'add_new_item'  => __('Add New Location', 'piano-booking'),
            ],
            'public'              => true,
            'has_archive'         => true,
            'show_in_rest'        => true,
            'show_ui'             => true,
            'show_in_menu'        => 'piano-booking',
            'menu_icon'           => 'dashicons-store',
            'supports'            => ['title', 'editor', 'thumbnail'],
            'rewrite'             => ['slug' => 'locations'],
        ]);

        register_post_type(self::ROOM, [
            'labels' => [
                'name'          => __('Rooms', 'piano-booking'),
                'singular_name' => __('Room', 'piano-booking'),
                'add_new_item'  => __('Add New Room', 'piano-booking'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'piano-booking',
            'show_in_rest' => true,
            'supports'     => ['title', 'editor', 'thumbnail'],
        ]);

        register_post_type(self::PACKAGE, [
            'labels' => [
                'name'          => __('Package Tiers', 'piano-booking'),
                'singular_name' => __('Package Tier', 'piano-booking'),
                'add_new_item'  => __('Add New Package Tier', 'piano-booking'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'piano-booking',
            'show_in_rest' => true,
            'supports'     => ['title', 'editor'],
        ]);

        register_post_type(self::BOOKING, [
            'labels' => [
                'name'          => __('Bookings', 'piano-booking'),
                'singular_name' => __('Booking', 'piano-booking'),
            ],
            'public'              => false,
            'show_in_menu'        => 'piano-booking',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'supports'            => ['author'],
        ]);

        register_post_type(self::PACKAGE_PURCHASE, [
            'labels' => [
                'name'          => __('Package Purchases', 'piano-booking'),
                'singular_name' => __('Package Purchase', 'piano-booking'),
            ],
            'public'              => false,
            'show_in_menu'        => 'piano-booking',
            'show_in_admin_bar'   => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'supports'            => ['author'],
        ]);
    }
}