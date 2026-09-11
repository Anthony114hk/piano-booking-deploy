<?php
namespace PianoBooking;

use PianoBooking\Frontend\Assets;
use PianoBooking\Frontend\Shortcodes;

if (!defined('ABSPATH')) exit;

class Frontend
{
    /**
     * Bootstrap frontend behavior: assets, shortcodes, AJAX endpoints.
     *
     * Note: Admin bootstrap is now done unconditionally in Plugin::boot(),
     * not here. This class only handles user-facing initialization.
     */
    public static function boot(): void
    {
        add_action('wp_enqueue_scripts', [Assets::class, 'enqueue']);
        Shortcodes::register();
        add_action('wp_ajax_pb_get_availability', [REST::class, 'availability']);
        add_action('wp_ajax_nopriv_pb_get_availability', [REST::class, 'availability']);
        add_action('wp_ajax_pb_create_booking', [REST::class, 'create_booking']);
        add_action('wp_ajax_nopriv_pb_create_booking', [REST::class, 'create_booking']);
        add_action('wp_ajax_pb_upload_proof', [REST::class, 'upload_proof']);
        // No wp_ajax_nopriv_ for upload_proof — login required.
        add_action('wp_ajax_pb_create_package_purchase', [REST::class, 'create_package_purchase']);
        add_action('wp_ajax_nopriv_pb_create_package_purchase', [REST::class, 'create_package_purchase']);
    }
}