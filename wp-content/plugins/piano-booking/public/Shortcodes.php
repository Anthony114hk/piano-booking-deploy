<?php
namespace PianoBooking\Frontend;

use PianoBooking\CPT;

if (!defined('ABSPATH')) exit;

class Shortcodes
{
    public static function register(): void
    {
        add_shortcode('pb_locations',        [self::class, 'locations']);
        add_shortcode('pb_booking_form',     [self::class, 'booking_form']);
        add_shortcode('pb_my_orders',        [self::class, 'my_orders']);
        add_shortcode('pb_payment_proof',    [self::class, 'payment_proof']);
        add_shortcode('pb_packages',         [self::class, 'packages']);
        // Packages flow is disabled for the public — redirect non-admins
        // BEFORE the page renders (template_redirect runs before
        // the_content → wp_safe_redirect works at this point).
        add_action('template_redirect',      [self::class, 'maybe_block_packages_page']);
    }

    /**
     * If a non-admin hits /packages/ (any sub-step), bounce them to home.
     * Admins keep access so they can verify the shortcode wiring.
     */
    public static function maybe_block_packages_page(): void
    {
        if (current_user_can('manage_options')) {
            return;
        }
        // is_page() needs to be queried against the loaded post; works after
        // template_redirect has identified the queried object.
        if (is_page('packages')) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    public static function locations(): string
    {
        $q = new \WP_Query(['post_type' => CPT::LOCATION, 'post_status' => 'publish', 'posts_per_page' => -1]);
        ob_start();
        echo '<div class="pb-locations">';
        while ($q->have_posts()) {
            $q->the_post();
            $id = get_the_ID();
            echo '<div class="pb-location-card">';
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<p>' . esc_html(get_post_meta($id, 'address', true)) . '</p>';
            echo '<a class="pb-btn" href="' . esc_url(add_query_arg(['pb_loc' => $id], home_url('/booking'))) . '">' . esc_html__('Book here', 'piano-booking') . '</a>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function booking_form(): string
    {
        $step = $_GET['pb_step'] ?? 'location';
        $view = "booking/step-{$step}.php";
        if (!file_exists(PB_PLUGIN_DIR . 'public/views/' . $view)) {
            $view = 'booking/step-location.php';
        }
        ob_start();
        include PB_PLUGIN_DIR . 'public/views/' . $view;
        return ob_get_clean();
    }

    public static function my_orders(): string
    {
        ob_start();
        include PB_PLUGIN_DIR . 'public/views/my-orders.php';
        return ob_get_clean();
    }

    public static function payment_proof(array $atts): string
    {
        ob_start();
        include PB_PLUGIN_DIR . 'public/views/proof-upload.php';
        return ob_get_clean();
    }

    public static function packages(): string
    {
        // Public access blocked via template_redirect (see maybe_block_packages_page).
        // Only admins reach this method (or anyone if the page guard is bypassed).
        ob_start();
        // Router: list / purchase / done based on query params
        if (!empty($_GET['pb_step']) && $_GET['pb_step'] === 'done') {
            include PB_PLUGIN_DIR . 'public/views/packages/done.php';
        } elseif (!empty($_GET['pb_pkg'])) {
            include PB_PLUGIN_DIR . 'public/views/packages/purchase.php';
        } else {
            include PB_PLUGIN_DIR . 'public/views/packages/list.php';
        }
        return ob_get_clean();
    }
}