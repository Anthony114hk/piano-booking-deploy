<?php
namespace PianoBooking\Admin;

use PianoBooking\CPT;
use PianoBooking\Meta_Boxes;
use PianoBooking\REST;
if (!defined('ABSPATH')) exit;

class Admin
{
    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'register_menu'], 9); // priority 9 → fires before WP's _add_post_type_submenus (priority 10) so our submenus sort first
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
        Bookings_List::register();
        Customers_List::register();
        Reports::register();
        Meta_Boxes::register();
        Admin_Cleanup::register();
        Admin_Access::register();
        add_action('wp_ajax_pb_admin_verify', [REST::class, 'admin_verify']);
        add_action('wp_ajax_pb_admin_reject',  [REST::class, 'admin_reject']);
        add_action('wp_ajax_pb_admin_cancel',  [REST::class, 'admin_cancel']);
        add_action('wp_ajax_pb_admin_activate_package', [REST::class, 'admin_activate_package']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            __('Piano Booking', 'piano-booking'),
            __('Piano Booking', 'piano-booking'),
            'manage_options',
            'piano-booking',
            '', // Empty — overridden by first submenu (see below)
            'dashicons-calendar-alt',
            25
        );
        // First submenu shares the parent slug → WP routes the parent menu URL here.
        // Without this, clicking "Piano Booking" lands on Locations (because pb_location
        // is the first CPT auto-submenu under this parent).
        add_submenu_page('piano-booking', __('Dashboard', 'piano-booking'), __('Dashboard', 'piano-booking'),
            'manage_options', 'piano-booking', [Dashboard::class, 'render']);
        add_submenu_page('piano-booking', __('Bookings', 'piano-booking'), __('Bookings', 'piano-booking'),
            'manage_options', 'pb-bookings', [Bookings_List::class, 'render']);
        add_submenu_page('piano-booking', __('Package Purchases', 'piano-booking'), __('Package Purchases', 'piano-booking'),
            'manage_options', 'pb-package-purchases', [Bookings_List::class, 'render_purchases']);
        add_submenu_page('piano-booking', __('Customers', 'piano-booking'), __('Customers', 'piano-booking'),
            'manage_options', 'pb-customers', [Customers_List::class, 'render']);
        add_submenu_page('piano-booking', __('Reports', 'piano-booking'), __('Reports', 'piano-booking'),
            'manage_options', 'pb-reports', [Reports::class, 'render']);
    }

    public static function enqueue(string $hook): void
    {
        wp_enqueue_style('pb-admin', PB_PLUGIN_URL . 'assets/css/admin.css', [], PB_VERSION);
        if (str_contains($hook, 'pb-')) {
            wp_enqueue_script('pb-admin-verify', PB_PLUGIN_URL . 'assets/js/admin-verify.js', ['jquery'], PB_VERSION, true);
            wp_localize_script('pb-admin-verify', 'PB_ADMIN', [
                'nonce' => wp_create_nonce('pb_admin'),
                'ajax'  => admin_url('admin-ajax.php'),
            ]);
        }
    }
}