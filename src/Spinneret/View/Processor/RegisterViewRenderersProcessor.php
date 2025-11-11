<?php

namespace Arakne\Spinneret\View\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Spinneret\View\Engine;
use Override;

use function assert;

final readonly class RegisterViewRenderersProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $engineDefinition = $builder->services[Engine::class];
        $renderers = [];
        $themeRenderers = [];

        foreach ($builder->findByTag(Renderer::class) as $service => $tags) {
            $service->public();

            foreach ($tags as $tag) {
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                assert($tag instanceof Renderer);

                if ($tag->theme === null) {
                    $renderers[$tag->response] = $service->id;
                } else {
                    $themeRenderers[$tag->theme][$tag->response] = $service->id;
                }
            }
        }

        $engineDefinition->set(6, $renderers);
        $engineDefinition->set(7, $themeRenderers);
    }
}
