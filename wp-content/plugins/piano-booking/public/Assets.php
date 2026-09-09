<?php
namespace PianoBooking\Frontend;

if (!defined('ABSPATH')) exit;

class Assets
{
    public static function enqueue(): void
    {
        wp_enqueue_style('pb-public', PB_PLUGIN_URL . 'assets/css/public.css', [], PB_VERSION);

        // Register both scripts, then localize. Localizing on slot-grid handle ensures
        // PB_AJAX is defined before slot-grid.js executes (booking-flow.js uses it too,
        // and depends on slot-grid as a peer).
        wp_register_script('pb-slot-grid',    PB_PLUGIN_URL . 'assets/js/slot-grid.js',    ['jquery'], PB_VERSION, true);
        wp_register_script('pb-booking-flow',  PB_PLUGIN_URL . 'assets/js/booking-flow.js',  ['jquery'], PB_VERSION, true);

        wp_localize_script('pb-slot-grid', 'PB_AJAX', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pb_public'),
        ]);

        wp_enqueue_script('pb-slot-grid');
        wp_enqueue_script('pb-booking-flow');
    }
}