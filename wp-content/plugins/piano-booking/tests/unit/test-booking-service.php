<?php
namespace PianoBooking\Tests\Unit;

use PianoBooking\Booking_Service;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic unit tests for Booking_Service::diff_hours().
 *
 * WP-dependent paths (wp_insert_post, wp_update_post, get_post_meta) are
 * covered by `wp eval` smoke tests at commit time.
 */
class TestBookingService extends TestCase
{
    public function test_diff_hours_one_hour(): void
    {
        $this->assertSame(1.0, Booking_Service::diff_hours('2099-12-31 14:00:00', '2099-12-31 15:00:00'));
    }

    public function test_diff_hours_thirty_minutes(): void
    {
        $this->assertSame(0.5, Booking_Service::diff_hours('2099-12-31 14:00:00', '2099-12-31 14:30:00'));
    }

    public function test_diff_hours_two_and_half_hours(): void
    {
        $this->assertSame(2.5, Booking_Service::diff_hours('2099-12-31 14:00:00', '2099-12-31 16:30:00'));
    }

    public function test_diff_hours_zero(): void
    {
        $this->assertSame(0.0, Booking_Service::diff_hours('2099-12-31 14:00:00', '2099-12-31 14:00:00'));
    }
}