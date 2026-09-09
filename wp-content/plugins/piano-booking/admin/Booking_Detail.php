<?php
namespace PianoBooking\Admin;

use PianoBooking\Booking_Service;
use PianoBooking\Payment;
if (!defined('ABSPATH')) exit;

class Booking_Detail
{
    public static function render(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $booking = get_post($id);
        if (!$booking || $booking->post_type !== 'pb_booking') {
            echo '<div class="wrap"><p>' . esc_html__('Booking not found.', 'piano-booking') . '</p></div>';
            return;
        }
        $inst = Payment::instructions($id);
        include PB_PLUGIN_DIR . 'admin/views/booking-detail.php';
    }
}