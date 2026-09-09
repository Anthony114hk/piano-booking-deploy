<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Slot_Lock
{
    private const TTL    = 300; // 5 min
    private const PREFIX = 'pb_slot_';

    /**
     * Atomically acquire a transient lock for a slot range.
     * Returns true if obtained, false if another caller holds it.
     */
    public static function acquire(int $roomId, string $startAt, string $endAt): bool
    {
        $key = self::key($roomId, $startAt, $endAt);
        if (get_transient($key)) return false;
        set_transient($key, '1', self::TTL);
        // Verify it actually took — defense against concurrent acquire.
        return get_transient($key) === '1';
    }

    public static function release(int $roomId, string $startAt, string $endAt): void
    {
        delete_transient(self::key($roomId, $startAt, $endAt));
    }

    public static function is_locked(int $roomId, string $startAt, string $endAt): bool
    {
        return (bool) get_transient(self::key($roomId, $startAt, $endAt));
    }

    public static function key(int $roomId, string $startAt, string $endAt): string
    {
        return self::PREFIX . $roomId . '_' . $startAt . '_' . $endAt;
    }
}