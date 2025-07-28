<?php

namespace Arakne\Spinneret\Container\Attribute;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;
use Override;

/**
 * Register the marked class as a service in the container.
 * This class is not final, so it can be extended to simplify the configuration of the service.
 *
 * @api
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Service implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * Make the service public.
         * A service public can be accessed directly from the container, using the `get()` method.
         * If false, the service can only be accessed through dependency injection.
         */
        public bool $public = false,

        /**
         * Tags to add to this service.
         *
         * @var list<string|object>
         */
        public array $tags = [],

        /**
         * Aliases to register for this service.
         *
         * @var list<string>
         */
        public array $aliases = [],
    ) {}

    #[Override]
    final public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $service->public = $service->public || $this->public;

        foreach ($this->tags as $tag) {
            $service->tag($tag);
        }

        foreach ($this->aliases as $alias) {
            $container->alias($alias, $service->id);
        }
    }
}
