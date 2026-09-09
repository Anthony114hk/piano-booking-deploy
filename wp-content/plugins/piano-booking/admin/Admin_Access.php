<?php
namespace PianoBooking\Admin;

if (!defined('ABSPATH')) exit;

/**
 * Restrict wp-admin/ to users who can manage_options.
 *
 * Subscribers and other low-privilege roles who try to open /wp-admin/
 * are bounced back to the home page. admin-ajax.php is exempted so
 * front-end AJAX calls (booking flow, package purchase, register…)
 * keep working for logged-in subscribers.
 *
 * Frontend-only users still keep WP login (/wp-login.php) so they can
 * change their password via the standard WP profile screen, but they
 * have no other admin surface.
 */
class Admin_Access
{
    public static function register(): void
    {
        add_action('admin_init', [self::class, 'maybe_block'], 1);
    }

    public static function maybe_block(): void
    {
        // Administrators keep full access
        if (current_user_can('manage_options')) {
            return;
        }

        // Logged-out visitors hit wp-login.php via separate flow.
        // Our block only applies to authenticated low-priv users trying
        // to reach the dashboard. Without this guard the redirect
        // would loop visitors between /wp-admin/ and /wp-login.php.
        if (!is_user_logged_in()) {
            return;
        }

        // Never block AJAX endpoints — the booking flow needs them.
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Allow the WP profile screen so subscribers can change their
        // password from the frontend. profile.php / user-edit.php are
        // the only admin pages a subscriber would normally need.
        global $pagenow;
        if (in_array($pagenow, ['profile.php', 'user-edit.php'], true)) {
            return;
        }

        wp_safe_redirect(home_url('/'));
        exit;
    }
}