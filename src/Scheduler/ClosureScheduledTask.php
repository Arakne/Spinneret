<?php

namespace Arakne\Spinneret\Scheduler;

use Closure;
use Override;
use ReflectionFunction;

use function is_bool;
use function spl_object_id;

/**
 * Adapter to run a Closure as a scheduled task.
 */
final readonly class ClosureScheduledTask implements ScheduledTaskInterface
{
    private string $name;

    public function __construct(
        /**
         * @var Closure():(void|bool)
         */
        private Closure $closure,
        private ScheduleDelayInterface $delay,

        /**
         * If true, the task will be run every time the scheduler runs, otherwise it will be run only once.
         */
        private bool $perpetual = false,

        /**
         * The name of the task. If null, a default name will be generated based on the closure's name.
         */
        ?string $name = null,
    ) {
        $this->name = $name ?? self::closureName($closure);
    }

    #[Override]
    public function name(): string
    {
        return $this->name;
    }

    #[Override]
    public function run(): bool
    {
        $ret = ($this->closure)();

        if (!is_bool($ret)) {
            return true;
        }

        return $ret;
    }

    #[Override]
    public function perpetual(): bool
    {
        return $this->perpetual;
    }

    #[Override]
    public function delay(): ScheduleDelayInterface
    {
        return $this->delay;
    }

    private static function closureName(Closure $closure): string
    {
        $ref = new ReflectionFunction($closure);

        if ($ref->isAnonymous()) {
            return 'Closure#' . spl_object_id($closure);
        }

        if ($ref->getClosureCalledClass() !== null) {
            return $ref->getClosureCalledClass()->getName() . '::' . $ref->getName();
        }

        return $ref->getName(); // @todo handle FCC
    }
}
