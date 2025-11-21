<?php

namespace Arakne\Tests\Spinneret\Time;

use Arakne\Spinneret\Time\SystemClock;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase
{
    #[Test]
    public function instance()
    {
        $clock = SystemClock::instance();

        $this->assertInstanceOf(SystemClock::class, $clock);
        $this->assertSame($clock, SystemClock::instance());
    }

    #[Test]
    public function now()
    {
        $clock = SystemClock::instance();
        $now = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $now);
        $this->assertLessThanOrEqual(1, abs($now->getTimestamp() - time()));
    }

    #[Test]
    public function withTimezone()
    {
        $clock = new SystemClock(new DateTimeZone('Arctic/Longyearbyen'));
        $now = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $now);
        $this->assertLessThanOrEqual(1, abs($now->getTimestamp() - time()));
        $this->assertEquals(new DateTimeZone('Arctic/Longyearbyen'), $now->getTimezone());
    }
}
