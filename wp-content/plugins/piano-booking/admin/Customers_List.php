<?php
namespace PianoBooking\Admin;

use PianoBooking\CPT;
use PianoBooking\Credits_Engine;
if (!defined('ABSPATH')) exit;

class Customers_List
{
    public static function register(): void {}

    public static function render(): void
    {
        $users = get_users(['role__in' => ['subscriber', 'customer']]);
        echo '<div class="wrap"><h1>' . esc_html__('Customers', 'piano-booking') . '</h1>';
        echo '<table class="widefat"><thead><tr><th>' . esc_html__('Name', 'piano-booking') . '</th><th>' . esc_html__('Email', 'piano-booking') . '</th><th>' . esc_html__('Total bookings', 'piano-booking') . '</th><th>' . esc_html__('Active credits', 'piano-booking') . '</th></tr></thead><tbody>';
        foreach ($users as $u) {
            $count = count(get_posts(['post_type' => CPT::BOOKING, 'author' => $u->ID, 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => -1]));
            $balance = Credits_Engine::balance($u->ID);
            echo '<tr><td>' . esc_html($u->display_name) . '</td><td>' . esc_html($u->user_email) . '</td><td>' . esc_html($count) . '</td><td>' . esc_html(number_format($balance, 1)) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}