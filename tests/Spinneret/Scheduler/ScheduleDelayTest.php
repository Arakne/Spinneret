<?php

namespace Arakne\Tests\Spinneret\Scheduler;

use Arakne\Spinneret\Scheduler\ScheduleDelay;
use Arakne\Spinneret\Scheduler\TimeUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScheduleDelayTest extends TestCase
{
	#[Test]
	public function millisecondsFactoryShouldCreateDelayInMilliseconds(): void
	{
		$delay = ScheduleDelay::milliseconds(42);

		$this->assertSame(42, $delay->value);
		$this->assertSame(TimeUnit::Millisecond, $delay->unit);
		$this->assertSame(1042, $delay->toMilliseconds(1000));
	}

	#[Test]
	public function secondsFactoryShouldCreateDelayInSeconds(): void
	{
		$delay = ScheduleDelay::seconds(3);

		$this->assertSame(3, $delay->value);
		$this->assertSame(TimeUnit::Second, $delay->unit);
		$this->assertSame(4000, $delay->toMilliseconds(1000));
	}

	#[Test]
	public function minutesFactoryShouldCreateDelayInMinutes(): void
	{
		$delay = ScheduleDelay::minutes(2);

		$this->assertSame(2, $delay->value);
		$this->assertSame(TimeUnit::Minutes, $delay->unit);
		$this->assertSame(121000, $delay->toMilliseconds(1000));
	}

	#[Test]
	public function hoursFactoryShouldCreateDelayInHours(): void
	{
		$delay = ScheduleDelay::hours(2);

		$this->assertSame(2, $delay->value);
		$this->assertSame(TimeUnit::Hours, $delay->unit);
		$this->assertSame(2 * 60 * 60 * 1000 + 1000, $delay->toMilliseconds(1000));
	}

	#[Test]
	#[DataProvider('toMillisecondsProvider')]
	public function toMillisecondsShouldOffsetOriginByUnitValue(
		int $origin,
		int $value,
		TimeUnit $unit,
		int $expected,
	): void {
		$delay = new ScheduleDelay($value, $unit);

		$this->assertSame($expected, $delay->toMilliseconds($origin));
	}

	public static function toMillisecondsProvider(): array
	{
		return [
			'millisecond unit' => [100, 5, TimeUnit::Millisecond, 105],
			'second unit' => [100, 2, TimeUnit::Second, 2100],
			'minute unit' => [100, 1, TimeUnit::Minutes, 60100],
			'hour unit' => [100, 1, TimeUnit::Hours, 3_600_100],
			'day unit' => [100, 1, TimeUnit::Days, 86_400_100],
			'zero delay keeps origin' => [1234, 0, TimeUnit::Second, 1234],
		];
	}

    #[Test]
    public function castToString()
    {
        $this->assertEquals('5 ms', (string) new ScheduleDelay(5, TimeUnit::Millisecond));
        $this->assertEquals('5 s', (string) new ScheduleDelay(5, TimeUnit::Second));
        $this->assertEquals('5 min', (string) new ScheduleDelay(5, TimeUnit::Minutes));
        $this->assertEquals('5 h', (string) new ScheduleDelay(5, TimeUnit::Hours));
        $this->assertEquals('5 d', (string) new ScheduleDelay(5, TimeUnit::Days));
    }
}
