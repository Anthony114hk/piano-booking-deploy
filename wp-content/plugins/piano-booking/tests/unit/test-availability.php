<?php
namespace PianoBooking\Tests\Unit;

use PianoBooking\Availability;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the pure-logic parts of Availability.
 *
 * The WP-dependent paths (for_room_and_date's WP_Query + transient lookup) are
 * covered by integration smoke tests at commit time (run `wp eval` to verify).
 */
class TestAvailability extends TestCase
{
    public function test_generates_28_slots_for_operating_day(): void
    {
        $slots = Availability::generate_slot_keys();
        $this->assertCount(28, $slots); // 9:00-23:00, 30-min = 28 slots
        $this->assertTrue($slots['09:00']);
        $this->assertTrue($slots['22:30']);
        $this->assertArrayNotHasKey('08:30', $slots);
        $this->assertArrayNotHasKey('23:00', $slots);
    }

    public function test_slot_overlaps_booking_marks_covered_slots(): void
    {
        $booking = ['start' => '2099-12-31 14:00:00', 'end' => '2099-12-31 15:00:00'];
        $this->assertTrue(Availability::slot_overlaps_booking('14:00', $booking));
        $this->assertTrue(Availability::slot_overlaps_booking('14:30', $booking));
        $this->assertFalse(Availability::slot_overlaps_booking('13:30', $booking));
        $this->assertFalse(Availability::slot_overlaps_booking('15:00', $booking));
    }

    public function test_slot_overlap_handles_multi_hour_bookings(): void
    {
        $booking = ['start' => '2099-12-31 14:00:00', 'end' => '2099-12-31 16:30:00'];
        $this->assertTrue(Availability::slot_overlaps_booking('14:00', $booking));
        $this->assertTrue(Availability::slot_overlaps_booking('16:00', $booking));
        $this->assertFalse(Availability::slot_overlaps_booking('16:30', $booking)); // end is exclusive
    }
}