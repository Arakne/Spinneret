<?php

namespace Arakne\Spinneret\Runner\Processor;

use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Runner\Runner;
use Override;
use Psr\Http\Server\MiddlewareInterface;

final readonly class RegisterMiddlewareProcessor implements ContainerBuilderProcessorInterface
{
    public function __construct(
        private string $target = Runner::class,
    ) {}

    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        // @todo sort by priority
        $taggedServices = $builder->findByTag(MiddlewareInterface::class);
        $middlewares = [];

        foreach ($taggedServices as $service => $tags) {
            $middlewares[] = new Reference($service->id);
        }

        $builder->services[$this->target]->set(3, $middlewares);
    }
}
