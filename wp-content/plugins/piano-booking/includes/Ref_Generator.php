<?php
namespace PianoBooking;

if (!defined('ABSPATH')) exit;

class Ref_Generator
{
    private const PREFIXES = [
        CPT::BOOKING          => 'BK',
        CPT::PACKAGE_PURCHASE => 'PK',
    ];

    public static function next(string $cpt): string
    {
        $key = self::option_key($cpt);
        $seq = (int) get_option($key, 0) + 1;
        update_option($key, $seq, false);
        return self::format($cpt, $seq);
    }

    public static function preview(string $cpt): string
    {
        $seq = (int) get_option(self::option_key($cpt), 0) + 1;
        return self::format($cpt, $seq);
    }

    private static function option_key(string $cpt): string
    {
        // Sequence is keyed by HKT date — resets at midnight HKT.
        $hktDate = (new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong')))->format('Ymd');
        return "pb_ref_seq_{$hktDate}_{$cpt}";
    }

    private static function format(string $cpt, int $seq): string
    {
        $prefix = self::PREFIXES[$cpt] ?? 'XX';
        $date = (new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong')))->format('Ymd');
        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
}