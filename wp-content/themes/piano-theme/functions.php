<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    load_theme_textdomain('piano-theme', get_template_directory() . '/languages');

    // Register nav menu location so block editor + front-end nav block can find it.
    register_nav_menus([
        'primary' => __('Primary Menu', 'piano-theme'),
        'footer'  => __('Footer Menu', 'piano-theme'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    // Theme stylesheet (currently just header metadata + the wp-block-columns
    // layout fallback that core block-library doesn't provide because we don't
    // enqueue it). WP block themes do NOT auto-enqueue style.css the way
    // classic themes did, so do it explicitly here.
    wp_enqueue_style(
        'piano-theme',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );

    // PB_PLUGIN_URL + PB_VERSION are defined by the piano-booking plugin's main file.
    if (defined('PB_PLUGIN_URL') && defined('PB_VERSION')) {
        wp_enqueue_style(
            'piano-theme-public',
            PB_PLUGIN_URL . 'assets/css/public.css',
            [],
            PB_VERSION
        );
    }
});