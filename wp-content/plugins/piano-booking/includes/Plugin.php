<?php
namespace PianoBooking;

use PianoBooking\Admin\Admin as Admin_Bootstrap;
use PianoBooking\Meta_Boxes;
use PianoBooking\Frontend\Frontend;
use PianoBooking\REST;

if (!defined('ABSPATH')) exit;

class Plugin
{
    public const VERSION = '0.1.0';

    public static function boot(): void
    {
        add_action('init', [CPT::class, 'register_all']);
        add_action('init', [Status::class, 'register_all']);
        add_action('init', [Meta::class, 'register_all']);
        add_action('add_meta_boxes', [Meta_Boxes::class, 'add']);
        Cron::register_hooks();
        add_action('init', [Cron::class, 'schedule'], 20);

        // Admin bootstrap: register menu + handlers regardless of context.
        // WordPress filters out admin_menu actions on non-admin requests anyway,
        // so this is safe to do unconditionally.
        Admin_Bootstrap::boot();

        // AJAX handlers MUST register on every request (including /wp-admin/admin-ajax.php
        // which sets is_admin()=true). Keep them outside the !is_admin() gate below.
        self::register_ajax_handlers();

        if (!is_admin()) {
            Frontend::boot();
        }

        load_plugin_textdomain('piano-booking', false, dirname(plugin_basename(PB_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Register AJAX endpoints that need to fire on BOTH wp-admin and front-end contexts.
     * admin-ajax.php is served from /wp-admin/ so is_admin() returns true there —
     * any AJAX action registration inside Frontend::boot() would be skipped.
     */
    private static function register_ajax_handlers(): void
    {
        add_action('wp_ajax_pb_get_availability',         [REST::class, 'availability']);
        add_action('wp_ajax_nopriv_pb_get_availability',   [REST::class, 'availability']);
        add_action('wp_ajax_pb_create_booking',           [REST::class, 'create_booking']);
        add_action('wp_ajax_nopriv_pb_create_booking',     [REST::class, 'create_booking']);
        add_action('wp_ajax_pb_upload_proof',             [REST::class, 'upload_proof']);
        add_action('wp_ajax_nopriv_pb_upload_proof',       [REST::class, 'upload_proof']);
        add_action('wp_ajax_pb_create_package_purchase',  [REST::class, 'create_package_purchase']);
        add_action('wp_ajax_nopriv_pb_create_package_purchase', [REST::class, 'create_package_purchase']);
        add_action('wp_ajax_pb_register_and_login',          [REST::class, 'register_and_login']);
        add_action('wp_ajax_nopriv_pb_register_and_login',    [REST::class, 'register_and_login']);
    }

    public static function activate(): void
    {
        Cron::schedule();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        Cron::unschedule();
        flush_rewrite_rules();
    }
}