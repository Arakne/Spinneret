<?php

namespace Arakne\Spinneret\Event\Compiler;

use Arakne\Spinneret\Event\EventDispatcher;
use Arakne\Spinneret\Event\EventSubscriberInterface;
use Closure;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function class_exists;
use function count;
use function is_int;
use function var_dump;

/**
 * Register event subscribers tagged with the EventSubscriberInterface::class tag.
 */
final readonly class RegisterSubscribersCompilerPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(EventDispatcher::class);
        $listeners = $definition->getArgument(1);

        foreach ($container->findTaggedServiceIds(EventSubscriberInterface::class) as $id => $tags) {
            foreach ($this->resolveListeners($container, $id) as $event => $methods) {
                foreach ($methods as $method) {
                    $listeners[$event][] = $this->createListenerService($container, $id, $method);
                }
            }
        }

        $definition->setArgument(1, $listeners);
    }

    public function createListenerService(ContainerBuilder $container, string $subscriberClass, string $method): string
    {
        $id = 'spinneret.event.listener.' . $subscriberClass . '::' . $method;

        $container->register($id, Closure::class)
            ->setFactory([Closure::class, 'fromCallable'])
            ->setArguments([[new Reference($subscriberClass), $method]])
            ->setPublic(true)
        ;

        return $id;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @return array<class-string, list<string>>
     * @throws ReflectionException
     */
    public function resolveListeners(ContainerBuilder $container, string $id): array
    {
        /** @var class-string<EventSubscriberInterface> $subscriberClass */
        $subscriberClass = $container->getDefinition($id)->getClass() ?? $id;

        $methods = $subscriberClass::getListenerMethods();
        $listeners = [];

        foreach ($methods as $event => $method) {
            if (is_int($event)) {
                $event = $this->resolveEventClass($subscriberClass, $method);
            }

            $listeners[$event][] = $method;
        }

        return $listeners;
    }

    /**
     * @param class-string<EventSubscriberInterface> $subscriberClass
     * @param string $method
     *
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveEventClass(string $subscriberClass, string $method): string
    {
        if (!method_exists($subscriberClass, $method)) {
            throw new LogicException("Method $method does not exist on $subscriberClass");
        }

        $reflection = new ReflectionMethod($subscriberClass, $method);
        $parameters = $reflection->getParameters();

        if (count($parameters) !== 1) {
            throw new LogicException("Listener {$subscriberClass}::{$method} must have exactly one parameter");
        }

        $type = $parameters[0]->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Listener {$subscriberClass}::{$method} must have a typed parameter, or use the event attribute to explicitly define the event class");
        }

        $type = $type->getName();

        if (!class_exists($type)) {
            throw new LogicException("The type $type is not a valid event class");
        }

        return $type;
    }
}
