<?php

namespace Arakne\Spinneret\Event\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Event\Attribute\EventListener;
use Arakne\Spinneret\Event\ContainerListenerProvider;
use Closure;
use Override;
use ReflectionMethod;

use function assert;
use function is_array;

/**
 * Register listeners tagged with the {@see EventListener} tag.
 *
 * It also parse all public methods of all services with the {@see EventListener} attribute
 * to automatically register them as listeners.
 */
final readonly class RegisterListenersProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $this->registerMethodListeners($builder);
        $this->setupContainerListenerProvider($builder);
    }

    private function registerMethodListeners(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            if (($reflection = $service->reflection()) === null) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(EventListener::class) as $reflectionAttribute) {
                    $listenerService = $builder
                        ->anonymous(Closure::class)
                        ->value(new Reference($service->id)->method($method->name)->fcc())
                        ->public()
                    ;

                    foreach ($reflectionAttribute->newInstance()->resolveWithReflectionMethod($method) as $attribute) {
                        $listenerService->tag($attribute);
                    }
                }
            }
        }
    }

    private function setupContainerListenerProvider(ContainerBuilder $builder): void
    {
        $provider = $builder->services[ContainerListenerProvider::class];
        $listeners = $provider->arguments[1] ?? [];
        assert(is_array($listeners));

        foreach ($builder->findByTag(EventListener::class) as $service => $tags) {
            foreach ($tags as $tag) {
                assert($tag->eventClass !== null);

                // @phpstan-ignore offsetAccess.nonOffsetAccessible
                $listeners[$tag->eventClass][] = $service->id;
            }
        }

        $provider->set(1, $listeners);
    }
}
