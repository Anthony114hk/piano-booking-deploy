<?php
namespace PianoBooking;

use PianoBooking\CPT;
if (!defined('ABSPATH')) exit;

class Meta_Boxes
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'add']);
        add_action('save_post_' . CPT::LOCATION, [self::class, 'save_location'], 10, 2);
        add_action('save_post_' . CPT::ROOM, [self::class, 'save_room'], 10, 2);
        add_action('save_post_' . CPT::PACKAGE, [self::class, 'save_package'], 10, 2);
    }

    public static function add(): void
    {
        add_meta_box('pb_location_details', __('Location Details', 'piano-booking'),
            [self::class, 'render_location'], CPT::LOCATION, 'normal', 'high');
        add_meta_box('pb_room_details', __('Room Details', 'piano-booking'),
            [self::class, 'render_room'], CPT::ROOM, 'normal', 'high');
        add_meta_box('pb_package_details', __('Package Details', 'piano-booking'),
            [self::class, 'render_package'], CPT::PACKAGE, 'normal', 'high');
    }

    public static function render_location(\WP_Post $post): void
    {
        wp_nonce_field('pb_save_meta', 'pb_meta_nonce');
        include PB_PLUGIN_DIR . 'admin/views/meta-box-location.php';
    }

    public static function render_room(\WP_Post $post): void
    {
        wp_nonce_field('pb_save_meta', 'pb_meta_nonce');
        include PB_PLUGIN_DIR . 'admin/views/meta-box-room.php';
    }

    public static function render_package(\WP_Post $post): void
    {
        wp_nonce_field('pb_save_meta', 'pb_meta_nonce');
        include PB_PLUGIN_DIR . 'admin/views/meta-box-package.php';
    }

    public static function save_location(int $post_id, \WP_Post $post): void
    {
        if (!self::can_save($post_id)) return;
        update_post_meta($post_id, 'address', sanitize_text_field($_POST['pb_location_address'] ?? ''));
        update_post_meta($post_id, 'phone', sanitize_text_field($_POST['pb_location_phone'] ?? ''));
        update_post_meta($post_id, 'opening_hours', wp_kses_post($_POST['pb_location_hours'] ?? '{}'));
        update_post_meta($post_id, 'map_embed_url', esc_url_raw($_POST['pb_location_map'] ?? ''));
    }

    public static function save_room(int $post_id, \WP_Post $post): void
    {
        if (!self::can_save($post_id)) return;
        update_post_meta($post_id, 'location_id', abs(intval($_POST['pb_room_location'] ?? 0)));
        update_post_meta($post_id, 'capacity', abs(intval($_POST['pb_room_capacity'] ?? 4)));
        update_post_meta($post_id, 'equipment', wp_kses_post($_POST['pb_room_equipment'] ?? '[]'));
        update_post_meta($post_id, 'hourly_rate', sanitize_text_field($_POST['pb_room_rate'] ?? '0'));
        update_post_meta($post_id, 'is_active', !empty($_POST['pb_room_active']));
    }

    public static function save_package(int $post_id, \WP_Post $post): void
    {
        if (!self::can_save($post_id)) return;
        update_post_meta($post_id, 'hours_included', sanitize_text_field($_POST['pb_pkg_hours'] ?? '0'));
        update_post_meta($post_id, 'price_hkd', sanitize_text_field($_POST['pb_pkg_price'] ?? '0'));
        update_post_meta($post_id, 'validity_days', abs(intval($_POST['pb_pkg_days'] ?? 30)));
        update_post_meta($post_id, 'is_active', !empty($_POST['pb_pkg_active']));
    }

    private static function can_save(int $post_id): bool
    {
        if (!isset($_POST['pb_meta_nonce'])) return false;
        if (!wp_verify_nonce($_POST['pb_meta_nonce'], 'pb_save_meta')) return false;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
        if (!current_user_can('edit_post', $post_id)) return false;
        return true;
    }
}