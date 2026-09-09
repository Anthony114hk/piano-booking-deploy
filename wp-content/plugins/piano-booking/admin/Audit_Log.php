<?php
namespace PianoBooking\Admin;

if (!defined('ABSPATH')) exit;

class Audit_Log
{
    public static function record(int $userId, string $action, array $context = []): void
    {
        $entry = ['user_id' => $userId, 'action' => $action, 'context' => $context, 'at' => current_time('mysql', true)];
        $logs = get_option('pb_audit_log', []);
        $logs[] = $entry;
        if (count($logs) > 5000) $logs = array_slice($logs, -5000);
        update_option('pb_audit_log', $logs, false);
    }

    public static function record_mark_paid(int $bookingId, int $staffId): void
    {
        self::record($staffId, 'booking_mark_paid', ['booking_id' => $bookingId]);
    }

    public static function record_reject(int $bookingId, int $staffId, string $reason): void
    {
        self::record($staffId, 'booking_reject', ['booking_id' => $bookingId, 'reason' => $reason]);
    }
}