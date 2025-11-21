<?php

namespace Arakne\Tests\Spinneret\Time;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Time\FixedClock;
use Arakne\Spinneret\Time\SystemClock;
use Arakne\Spinneret\Time\TimeConfiguration;
use Arakne\Spinneret\Time\TimeModule;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class TimeModuleTest extends TestCase
{
    #[Test]
    public function register()
    {
        $container = new ContainerBuilder(registerAsPublic: true);

        new TimeModule()->register($container);
        $container->set(new TimeConfiguration());
        $container = $container->build();

        $this->assertTrue($container->has(SystemClock::class));
        $this->assertTrue($container->has(ClockInterface::class));
        $this->assertTrue($container->has(DateTimeZone::class));

        $this->assertSame($container->get(SystemClock::class), $container->get(ClockInterface::class));
        $this->assertEquals(new DateTimeZone('UTC'), $container->get(DateTimeZone::class));

        $this->assertEquals(new DateTimeZone('UTC'), $container->get(SystemClock::class)->now()->getTimezone());
    }

    #[Test]
    public function registerWithFixedTime()
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $config = new TimeConfiguration(
            fixedTime: new DateTimeImmutable('2024-01-01T12:00:00+00:00'),
        );

        new TimeModule($config)->register($container);
        $container->set($config);
        $container = $container->build();

        $this->assertTrue($container->has(SystemClock::class));
        $this->assertTrue($container->has(FixedClock::class));
        $this->assertTrue($container->has(ClockInterface::class));
        $this->assertTrue($container->has(DateTimeZone::class));

        $this->assertSame($container->get(FixedClock::class), $container->get(ClockInterface::class));
        $this->assertEquals(new DateTimeZone('UTC'), $container->get(DateTimeZone::class));

        $this->assertEquals(new DateTimeZone('UTC'), $container->get(SystemClock::class)->now()->getTimezone());
        $this->assertEquals(new DateTimeImmutable('2024-01-01T12:00:00+00:00'), $container->get(ClockInterface::class)->now());

        $container->get(FixedClock::class)->setNow(new DateTimeImmutable('2024-06-15T08:30:00+00:00'));
        $this->assertEquals(new DateTimeImmutable('2024-06-15T08:30:00+00:00'), $container->get(ClockInterface::class)->now());
    }

    #[Test]
    public function registerWithCustomTimeZone()
    {
        $container = new ContainerBuilder(registerAsPublic: true);

        new TimeModule()->register($container);
        $container->set(new TimeConfiguration(
            timeZone: new DateTimeZone('Europe/Paris'),
        ));
        $container = $container->build();

        $this->assertTrue($container->has(SystemClock::class));
        $this->assertTrue($container->has(ClockInterface::class));
        $this->assertTrue($container->has(DateTimeZone::class));

        $this->assertSame($container->has(SystemClock::class), $container->has(ClockInterface::class));
        $this->assertEquals(new DateTimeZone('Europe/Paris'), $container->get(DateTimeZone::class));

        $this->assertEquals(new DateTimeZone('Europe/Paris'), $container->get(SystemClock::class)->now()->getTimezone());
    }
}
