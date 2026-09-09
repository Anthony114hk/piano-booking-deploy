<?php
namespace PianoBooking\Frontend;

use PianoBooking\Frontend\Assets;
use PianoBooking\Frontend\Shortcodes;

if (!defined('ABSPATH')) exit;

class Frontend
{
    /**
     * Front-end-only bootstrap: assets, shortcodes.
     *
     * AJAX endpoints are registered in Plugin::boot() so they work on
     * /wp-admin/admin-ajax.php too (where is_admin()=true would skip this class).
     */
    public static function boot(): void
    {
        add_action('wp_enqueue_scripts', [Assets::class, 'enqueue']);
        Shortcodes::register();
    }
}