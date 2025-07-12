<?php

namespace Arakne\Spinneret\Presenter\Compiler;

use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class RegisterPresentersCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $dispatcherDefinition = $container->getDefinition(PresenterDispatcher::class);
        $presenters = [];

        foreach ($container->findTaggedServiceIds(PresenterInterface::class) as $id => $tags) {
            $container->findDefinition($id)->setPublic(true);

            foreach ($tags as $tag) {
                $presenters[$tag['request']] = $id;
            }
        }

        $dispatcherDefinition->setArgument(1, $presenters);
    }
}
