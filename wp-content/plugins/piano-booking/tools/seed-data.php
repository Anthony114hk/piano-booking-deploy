<?php
/**
 * WP-CLI seed commands.
 *
 * Adds `wp pb seed` to seed a sample location + 2 rooms + 1 package tier.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

use PianoBooking\CPT;

\WP_CLI::add_command('pb seed', function () {
    // 1 location
    $loc_id = wp_insert_post([
        'post_type'    => CPT::LOCATION,
        'post_title'   => 'Central Studio',
        'post_status'  => 'publish',
        'post_content' => 'Flagship studio in Central.',
    ]);
    if (is_wp_error($loc_id)) {
        \WP_CLI::error('Failed to create location: ' . $loc_id->get_error_message());
    }
    update_post_meta($loc_id, 'address', '15/F, Central Tower, Central, HK');
    update_post_meta($loc_id, 'phone', '+852 2345 6789');
    update_post_meta($loc_id, 'opening_hours', wp_json_encode([
        'mon' => '09:00-23:00', 'tue' => '09:00-23:00',
        'wed' => '09:00-23:00', 'thu' => '09:00-23:00',
        'fri' => '09:00-23:00', 'sat' => '09:00-23:00',
        'sun' => '10:00-22:00',
    ]));
    update_post_meta($loc_id, 'map_embed_url', 'https://www.google.com/maps/embed?pb=!1m0');

    // 2 rooms
    foreach ([['A', 4, ['upright']], ['B', 6, ['grand']]] as [$name, $cap, $eq]) {
        $rid = wp_insert_post([
            'post_type'   => CPT::ROOM,
            'post_title'  => "Studio $name",
            'post_status' => 'publish',
        ]);
        update_post_meta($rid, 'location_id', $loc_id);
        update_post_meta($rid, 'capacity', $cap);
        update_post_meta($rid, 'equipment', wp_json_encode($eq));
        update_post_meta($rid, 'hourly_rate', '80.00');
        update_post_meta($rid, 'is_active', true);
    }

    // 1 package tier
    $pkg_id = wp_insert_post([
        'post_type'   => CPT::PACKAGE,
        'post_title'  => '12-Hour Monthly Pass',
        'post_status' => 'publish',
    ]);
    update_post_meta($pkg_id, 'hours_included', '12.0');
    update_post_meta($pkg_id, 'price_hkd', '650.00');
    update_post_meta($pkg_id, 'validity_days', 30);
    update_post_meta($pkg_id, 'is_active', true);

    \WP_CLI::success('Seeded 1 location, 2 rooms, 1 package tier.');
});