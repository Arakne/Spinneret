<?php

namespace Arakne\Spinneret\Bus\Attribute;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;
use Override;

// @todo doc
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class MessageHandler implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * The message class that this handler will process.
         * If not provided, it will be resolved from the handler's method parameter type.
         *
         * @var class-string|null
         */
        public ?string $message = null,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $service->tag($this);
    }
}
