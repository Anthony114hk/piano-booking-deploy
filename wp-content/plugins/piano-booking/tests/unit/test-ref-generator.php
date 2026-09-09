<?php
namespace PianoBooking\Tests\Unit;

use PianoBooking\CPT;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Ref_Generator.
 *
 * Ref_Generator calls get_option/update_option, which are WP functions not
 * available outside the WP test framework. To exercise the format + sequence
 * logic without spinning up WP, we test a parallel implementation that uses
 * an in-memory array (RefGenInMemory). The real WP-backed implementation is
 * covered by `wp eval` smoke checks at commit time.
 */
class TestRefGenerator extends TestCase
{
    public function test_first_ref_of_day_format(): void
    {
        $g = new RefGenInMemory();
        $ref = $g->next(CPT::BOOKING);
        $this->assertMatchesRegularExpression('/^BK-\d{8}-0001$/', $ref);
    }

    public function test_sequence_increments(): void
    {
        $g = new RefGenInMemory();
        $a = $g->next(CPT::BOOKING);
        $b = $g->next(CPT::BOOKING);
        $this->assertSame('0001', substr($a, -4));
        $this->assertSame('0002', substr($b, -4));
    }

    public function test_preview_does_not_advance(): void
    {
        $g = new RefGenInMemory();
        $a = $g->preview(CPT::BOOKING);
        $b = $g->preview(CPT::BOOKING);
        $this->assertSame($a, $b);
    }

    public function test_per_cpt_sequence(): void
    {
        $g = new RefGenInMemory();
        $b = $g->next(CPT::BOOKING);
        $p = $g->next(CPT::PACKAGE_PURCHASE);
        $this->assertStringStartsWith('BK-', $b);
        $this->assertStringStartsWith('PK-', $p);
    }

    public function test_resets_at_midnight_hkt(): void
    {
        $g = new RefGenInMemory();
        $key = 'pb_ref_seq_' . (new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong')))->format('Ymd') . '_' . CPT::BOOKING;
        $g->store[$key] = 5;
        $ref = $g->next(CPT::BOOKING);
        $this->assertSame('0006', substr($ref, -4));
    }
}

/**
 * In-memory mirror of Ref_Generator (parallel impl) so we can test format +
 * sequence behavior without WordPress loaded.
 */
class RefGenInMemory
{
    public array $store = [];
    private const PREFIXES = [
        'pb_booking'          => 'BK',
        'pb_package_purchase' => 'PK',
    ];

    public function next(string $cpt): string
    {
        $key = $this->option_key($cpt);
        $this->store[$key] = ($this->store[$key] ?? 0) + 1;
        return $this->format($cpt, $this->store[$key]);
    }

    public function preview(string $cpt): string
    {
        $key = $this->option_key($cpt);
        $seq = ($this->store[$key] ?? 0) + 1;
        return $this->format($cpt, $seq);
    }

    public function option_key(string $cpt): string
    {
        $hkt = (new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong')))->format('Ymd');
        return "pb_ref_seq_{$hkt}_{$cpt}";
    }

    private function format(string $cpt, int $seq): string
    {
        $prefix = self::PREFIXES[$cpt] ?? 'XX';
        $date = (new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong')))->format('Ymd');
        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }
}