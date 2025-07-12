<?php

namespace Arakne\Spinneret\View\Compiler;

use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class RegisterViewRenderersCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $engineDefinition = $container->getDefinition(Engine::class);
        $renderers = [];

        foreach ($container->findTaggedServiceIds(ViewRendererInterface::class) as $id => $tags) {
            $container->findDefinition($id)->setPublic(true);
            $renderers[$tags[0]['response']] = $id;
        }

        $engineDefinition->setArgument(5, $renderers);
    }
}
