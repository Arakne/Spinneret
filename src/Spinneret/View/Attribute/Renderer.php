<?php

namespace Arakne\Spinneret\View\Attribute;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;
use Override;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Renderer implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * The response DTO class that this renderer handles.
         *
         * @var class-string
         */
        public string $response,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $service->public = true;
        $service->tag($this);
    }
}
