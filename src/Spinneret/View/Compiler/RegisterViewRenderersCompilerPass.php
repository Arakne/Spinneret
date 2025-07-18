<?php

namespace Arakne\Spinneret\View\Compiler;

use Arakne\Spinneret\View\Engine;
use Arakne\Spinneret\View\ViewRendererInterface;
use InvalidArgumentException;
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

            /** @var array<string, mixed> $tag */
            foreach ($tags as $tag) {
                if (!isset($tag['response']) || !is_string($tag['response'])) {
                    throw new InvalidArgumentException(sprintf(
                        'The "response" attribute of the "%s" tag on service "%s" must be a string.',
                        ViewRendererInterface::class,
                        $id
                    ));
                }

                $renderers[$tag['response']] = $id;
            }
        }

        $engineDefinition->setArgument(5, $renderers);
    }
}
