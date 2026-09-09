<?php
namespace PianoBooking\Tests\Unit;

use PianoBooking\Slot_Lock;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Slot_Lock key generation (pure function).
 *
 * WP transient integration is exercised via wp eval at commit time.
 */
class TestSlotLock extends TestCase
{
    public function test_key_includes_room_and_times(): void
    {
        $k = Slot_Lock::key(42, '2099-12-31 10:00:00', '2099-12-31 10:30:00');
        $this->assertSame('pb_slot_42_2099-12-31 10:00:00_2099-12-31 10:30:00', $k);
    }

    public function test_different_rooms_produce_different_keys(): void
    {
        $a = Slot_Lock::key(1, '2099-12-31 10:00:00', '2099-12-31 10:30:00');
        $b = Slot_Lock::key(2, '2099-12-31 10:00:00', '2099-12-31 10:30:00');
        $this->assertNotSame($a, $b);
    }

    public function test_different_ranges_produce_different_keys(): void
    {
        $a = Slot_Lock::key(42, '2099-12-31 10:00:00', '2099-12-31 10:30:00');
        $b = Slot_Lock::key(42, '2099-12-31 10:30:00', '2099-12-31 11:00:00');
        $this->assertNotSame($a, $b);
    }
}