<?php
namespace PianoBooking\Admin;

if (!defined('ABSPATH')) exit;

/**
 * Clean up the wp-admin sidebar for booking-site staff.
 *
 * Goals:
 *  - Hide menus that don't belong on a booking site (Posts, Comments, Media, Appearance, Plugins, Tools)
 *  - Keep them visible for users with the `manage_options` capability (administrators)
 *  - Rename a few default labels to match our domain
 */
class Admin_Cleanup
{
    public static function register(): void
    {
        // Skip for administrators — they get the full WP menu.
        // Uncomment the next line to ALWAYS hide (even for admins):
        //   add_action('admin_menu', [self::class, 'hide_menus'], 999);
        add_action('admin_menu', [self::class, 'hide_menus'], 999);
        add_action('admin_menu', [self::class, 'relabel_dashboard'], 999);
        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', [self::class, 'reorder_menu']);
    }

    /**
     * Hide menus that don't belong on a booking site.
     * Targets non-admin roles (studio staff who only verify payments / view reports).
     */
    public static function hide_menus(): void
    {
        if (current_user_can('manage_options')) {
            return; // administrators keep everything
        }

        // Top-level menus to remove
        remove_menu_page('edit.php');                  // Posts
        remove_menu_page('upload.php');                // Media
        remove_menu_page('edit-comments.php');         // Comments
        remove_menu_page('themes.php');                // Appearance
        remove_menu_page('plugins.php');               // Plugins
        remove_menu_page('tools.php');                 // Tools
        remove_menu_page('options-general.php');       // Settings (general)
        remove_menu_page('edit.php?post_type=wp_block'); // Reusable blocks
    }

    /**
     * Rename a few default labels so the admin makes sense to a non-WP-savvy studio owner.
     */
    public static function relabel_dashboard(): void
    {
        global $menu, $submenu;

        // Rename Users → Customers (it's the same WP users list, but for a booking site
        // "Customers" makes more sense)
        if (isset($submenu['users.php'])) {
            foreach ($submenu['users.php'] as &$item) {
                if ($item[0] === 'All Users') {
                    $item[0] = __('All Customers', 'piano-booking');
                }
                if ($item[0] === 'Add New User') {
                    $item[0] = __('Add New Customer', 'piano-booking');
                }
            }
        }
    }

    /**
     * Reorder top-level menus: Piano Booking goes right after Dashboard.
     */
    public static function reorder_menu(array $order): array
    {
        $piano_booking_key = array_search('piano-booking', $order, true);
        if ($piano_booking_key === false) {
            return $order;
        }

        // Move Piano Booking to position 2 (after Dashboard at 0, separator at 1)
        $pb = $order[$piano_booking_key];
        unset($order[$piano_booking_key]);
        array_splice($order, 1, 0, [$pb]);

        return $order;
    }
}