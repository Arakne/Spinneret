<?php

namespace Arakne\Spinneret\Container\Builder\Configurator;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Closure;
use Override;

use function is_a;

/**
 * Apply configurator to a service if it is an instance of a specific class or interface.
 *
 * @template T as object
 */
final readonly class InstanceOfConfigurator implements ConfiguratorInterface
{
    public function __construct(
        /**
         * @var class-string<T>
         */
        private string $type,

        /**
         * @var Closure(ServiceBuilder, ContainerBuilder): void
         */
        private Closure $configurator,
    ) {}

    #[Override]
    public function supports(ServiceBuilder $service): bool
    {
        return $service->class !== null && is_a($service->class, $this->type, true);
    }

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $containerBuilder): void
    {
        ($this->configurator)($service, $containerBuilder);
    }
}
