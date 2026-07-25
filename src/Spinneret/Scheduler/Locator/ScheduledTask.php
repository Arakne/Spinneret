<?php

namespace Arakne\Spinneret\Scheduler\Locator;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Scheduler\ClosureScheduledTask;
use Arakne\Spinneret\Scheduler\ScheduleDelayInterface;
use Arakne\Spinneret\Scheduler\ScheduledTaskInterface;
use Attribute;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;

use function sprintf;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class ScheduledTask implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * The delay before the task is run.
         * This value must be provided if the task is a method, otherwise it will be ignored.
         */
        public ?ScheduleDelayInterface $delay = null,

        /**
         * If true, the task will be run every time the scheduler runs, otherwise it will be run only once.
         * This value is ignored if the attribute is applied to a class implementing {@see ScheduledTaskInterface}.
         */
        public bool $perpetual = true,

        /**
         * The name of the task. If null, a default name will be generated based on the class or method name.
         * This value is ignored if the attribute is applied to a class implementing {@see ScheduledTaskInterface}.
         */
        public ?string $name = null,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $reflection = $service->reflection();

        if (!$reflection) {
            throw new LogicException(sprintf('The %s attribute can only be applied to services with a class.', self::class));
        }

        if ($reflection->isSubclassOf(ScheduledTaskInterface::class)) {
            $service->tag($this);
            return;
        }

        try {
            $method = $service->reflection()?->getMethod('__invoke');
        } catch (ReflectionException) {
            $method = null;
        }

        if ($method === null || $method->getNumberOfRequiredParameters() !== 0) {
            throw new LogicException(
                sprintf(
                    'Service %s must have a public __invoke method without arguments to be used as a scheduled task.',
                    $service->id
                )
            );
        }

        $this->registerServiceClosureAsTask($container, $service, $method);
    }

    /**
     * Register the service method as a scheduled task on the container.
     *
     * @internal
     */
    public function registerServiceClosureAsTask(ContainerBuilder $container, ServiceBuilder $service, ReflectionMethod $method): void
    {
        if ($this->delay === null) {
            throw new LogicException(
                sprintf(
                    'The delay parameter must be provided for service %s when using the %s attribute on a method.',
                    $service->id,
                    self::class
                )
            );
        }

        $container
            ->anonymous(ClosureScheduledTask::class, [
                new Reference($service->id)->method($method->name)->fcc(),
                $this->delay,
                $this->perpetual,
                $this->name,
            ])
            ->tag($this)
        ;
    }
}
