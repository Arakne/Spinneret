<?php

namespace Arakne\Spinneret\Presenter\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Presenter\PresenterDispatcher;
use Override;

use function assert;

final readonly class RegisterPresentersProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $dispatcherDefinition = $builder->services[PresenterDispatcher::class] ?? null;
        $presenters = [];

        foreach ($builder->findByTag(Presenter::class) as $service => $tags) {
            $service->public = true;

            foreach ($tags as $tag) {
                assert($tag instanceof Presenter);
                $presenters[$tag->request] = $service->id;
            }
        }

        $dispatcherDefinition->arguments[1] = $presenters;
    }
}
