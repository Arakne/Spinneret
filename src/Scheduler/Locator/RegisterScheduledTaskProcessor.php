<?php

namespace Arakne\Spinneret\Scheduler\Locator;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use LogicException;
use Override;
use ReflectionMethod;

use function assert;
use function sprintf;

/**
 * Register tasks tagged with the {@see ScheduledTask} tag.
 *
 * It also parse all public methods of all services with the {@see ScheduledTask} attribute
 * to automatically register them as task.
 */
final readonly class RegisterScheduledTaskProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            if (($reflection = $service->reflection()) === null) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(ScheduledTask::class) as $reflectionAttribute) {
                    if ($method->getNumberOfRequiredParameters() !== 0) {
                        throw new LogicException(sprintf(
                            'Method %s::%s cannot be registered as a scheduled task because it has required parameters.',
                            $service->id,
                            $method->name
                        ));
                    }

                    $tag = $reflectionAttribute->newInstance();
                    // @phpstan-ignore instanceof.alwaysTrue
                    assert($tag instanceof ScheduledTask);

                    $tag->registerServiceClosureAsTask($builder, $service, $method);
                }
            }
        }
    }
}
