<?php

namespace Arakne\Tests\Spinneret\Scheduler;

use Arakne\Spinneret\Scheduler\ClosureScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelay;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function strlen;

class ClosureScheduledTaskTest extends TestCase
{
	#[Test]
	public function shouldUseProvidedName(): void
	{
		$task = new ClosureScheduledTask(
			static function (): bool {
				return true;
			},
			ScheduleDelay::milliseconds(1),
			name: 'Custom task',
		);

		$this->assertSame('Custom task', $task->name());
	}

	#[Test]
	public function shouldGenerateNameForAnonymousClosureWhenMissing(): void
	{
		$closure = static function (): bool {
			return true;
		};

		$task = new ClosureScheduledTask(
			$closure,
			ScheduleDelay::milliseconds(1),
		);

		$this->assertSame('Closure#' . spl_object_id($closure), $task->name());
	}

    private function instanceTask(): void
    {
    }

    #[Test]
    public function shouldGeneratedNameForObjectFcc()
    {

        $task = new ClosureScheduledTask(
            $this::instanceTask(...),
            ScheduleDelay::milliseconds(1),
        );

        $this->assertSame('Arakne\Tests\Spinneret\Scheduler\ClosureScheduledTaskTest::instanceTask', $task->name());
    }

    public static function staticTask(): void
    {
    }

    #[Test]
    public function shouldGeneratedNameForStaticFcc()
    {

        $task = new ClosureScheduledTask(
            self::staticTask(...),
            ScheduleDelay::milliseconds(1),
        );

        $this->assertSame('Arakne\Tests\Spinneret\Scheduler\ClosureScheduledTaskTest::staticTask', $task->name());
    }

    #[Test]
    public function shouldGeneratedNameForGlobalFcc()
    {

        $task = new ClosureScheduledTask(
            strlen(...),
            ScheduleDelay::milliseconds(1),
        );

        $this->assertSame('strlen', $task->name());
    }

    #[Test]
    public function shouldGeneratedNameForArrayFcc()
    {

        $task = new ClosureScheduledTask(
            [ClosureScheduledTaskTest::class, 'staticTask'](...),
            ScheduleDelay::milliseconds(1),
        );

        $this->assertSame('Arakne\Tests\Spinneret\Scheduler\ClosureScheduledTaskTest::staticTask', $task->name());
    }

    #[Test]
	public function runShouldReturnBooleanResultWhenClosureReturnsBoolean(): void
	{
		$trueTask = new ClosureScheduledTask(
			static function (): bool {
				return true;
			},
			ScheduleDelay::milliseconds(1),
		);
		$falseTask = new ClosureScheduledTask(
			static function (): bool {
				return false;
			},
			ScheduleDelay::milliseconds(1),
		);

		$this->assertTrue($trueTask->run());
		$this->assertFalse($falseTask->run());
	}

	#[Test]
	public function runShouldReturnTrueWhenClosureReturnsVoidOrNonBoolean(): void
	{
		$voidTask = new ClosureScheduledTask(
			static function (): void {
			},
			ScheduleDelay::milliseconds(1),
		);
		$stringTask = new ClosureScheduledTask(
			static function (): string {
				return 'ok';
			},
			ScheduleDelay::milliseconds(1),
		);

		$this->assertTrue($voidTask->run());
		$this->assertTrue($stringTask->run());
	}

	#[Test]
	public function shouldExposePerpetualFlagAndDelay(): void
	{
		$delay = ScheduleDelay::seconds(2);

		$perpetualTask = new ClosureScheduledTask(
			static function (): bool {
				return true;
			},
			$delay,
			perpetual: true,
		);
		$singleTask = new ClosureScheduledTask(
			static function (): bool {
				return true;
			},
			$delay,
		);

		$this->assertTrue($perpetualTask->perpetual());
		$this->assertFalse($singleTask->perpetual());
		$this->assertSame($delay, $perpetualTask->delay());
	}
}
