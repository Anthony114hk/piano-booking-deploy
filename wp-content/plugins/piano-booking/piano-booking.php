<?php
/**
 * Plugin Name: Piano Studio Booking
 * Description: Piano room booking system with manual payment reconciliation
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Piano Booking Dev
 * Text Domain: piano-booking
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PB_VERSION', '0.1.0');
define('PB_PLUGIN_FILE', __FILE__);
define('PB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PB_PLUGIN_DIR . 'vendor/autoload.php';

\register_activation_hook(__FILE__, ['PianoBooking\\Plugin', 'activate']);
\register_deactivation_hook(__FILE__, ['PianoBooking\\Plugin', 'deactivate']);

add_action('plugins_loaded', function () {
    \PianoBooking\Plugin::boot();
});