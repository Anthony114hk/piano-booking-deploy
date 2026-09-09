<?php
namespace PianoBooking\Tests\Unit;

use PianoBooking\Plugin;
use PHPUnit\Framework\TestCase;

class TestSmoke extends TestCase
{
    public function test_plugin_class_exists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    public function test_plugin_version_constant(): void
    {
        $this->assertSame('0.1.0', Plugin::VERSION);
    }
}