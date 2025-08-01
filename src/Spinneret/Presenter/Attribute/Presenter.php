<?php

namespace Arakne\Spinneret\Presenter\Attribute;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;
use Override;

// @todo doc
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Presenter implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * The request DTO class that this presenter handles.
         *
         * @var class-string
         */
        public string $request,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $service->public = true;
        $service->tag($this);
    }
}
