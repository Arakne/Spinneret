<?php

namespace Arakne\Spinneret\Runner\CompilerPass;

use Arakne\Spinneret\Runner\Runner;
use Override;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class RegisterMiddlewareCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private string $target = Runner::class,
    ) {}

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        // @todo sort by priority
        $taggedServices = $container->findTaggedServiceIds(MiddlewareInterface::class);
        $middlewares = [];

        foreach ($taggedServices as $id => $tags) {
            $middlewares[] = new Reference($id);
        }

        $container->getDefinition($this->target)->setArgument(3, $middlewares);
    }
}
