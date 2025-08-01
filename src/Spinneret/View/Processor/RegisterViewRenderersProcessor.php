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

        foreach ($builder->findByTag(Renderer::class) as $service => $tags) {
            $service->public = true;

            foreach ($tags as $tag) {
                assert($tag instanceof Renderer);
                $renderers[$tag->response] = $service->id;
            }
        }

        $engineDefinition->arguments[5] = $renderers;
    }
}
