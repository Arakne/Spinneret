<?php

namespace Arakne\Spinneret\Event\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Event\EventDispatcher;
use Arakne\Spinneret\Event\EventSubscriberInterface;
use Closure;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

use function class_exists;
use function count;
use function is_int;

/**
 * Register event subscribers tagged with the EventSubscriberInterface::class tag.
 *
 * @todo migrate
 */
final readonly class RegisterSubscribersProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        //$definition = $builder->getDefinition(EventDispatcher::class);
        //
        ///** @var array<string, list<string>> $listeners */
        //$listeners = $definition->getArgument(1);
        //
        //foreach ($builder->findTaggedServiceIds(EventSubscriberInterface::class) as $id => $tags) {
        //    foreach ($this->resolveListeners($builder, $id) as $event => $methods) {
        //        foreach ($methods as $method) {
        //            $listeners[$event][] = $this->createListenerService($builder, $id, $method);
        //        }
        //    }
        //}
        //
        //$definition->setArgument(1, $listeners);
    }
    //
    //public function createListenerService(ContainerBuilder $builder, string $subscriberClass, string $method): string
    //{
    //    $id = 'spinneret.event.listener.' . $subscriberClass . '::' . $method;
    //
    //    $builder->register($id, Closure::class)
    //        ->setFactory([Closure::class, 'fromCallable'])
    //        ->setArguments([[new Reference($subscriberClass), $method]])
    //        ->setPublic(true)
    //    ;
    //
    //    return $id;
    //}
    //
    ///**
    // * @param ContainerBuilder $builder
    // * @param string $id
    // * @return array<class-string, list<string>>
    // * @throws ReflectionException
    // */
    //public function resolveListeners(ContainerBuilder $builder, string $id): array
    //{
    //    /** @var class-string<EventSubscriberInterface> $subscriberClass */
    //    $subscriberClass = $builder->getDefinition($id)->getClass() ?? $id;
    //
    //    $methods = $subscriberClass::getListenerMethods();
    //    $listeners = [];
    //
    //    foreach ($methods as $event => $method) {
    //        if (is_int($event)) {
    //            $event = $this->resolveEventClass($subscriberClass, $method);
    //        }
    //
    //        $listeners[$event][] = $method;
    //    }
    //
    //    return $listeners;
    //}
    //
    ///**
    // * @param class-string<EventSubscriberInterface> $subscriberClass
    // * @param string $method
    // *
    // * @return class-string
    // *
    // * @throws ReflectionException
    // */
    //public function resolveEventClass(string $subscriberClass, string $method): string
    //{
    //    if (!method_exists($subscriberClass, $method)) {
    //        throw new LogicException("Method $method does not exist on $subscriberClass");
    //    }
    //
    //    $reflection = new ReflectionMethod($subscriberClass, $method);
    //    $parameters = $reflection->getParameters();
    //
    //    if (count($parameters) !== 1) {
    //        throw new LogicException("Listener {$subscriberClass}::{$method} must have exactly one parameter");
    //    }
    //
    //    $type = $parameters[0]->getType();
    //
    //    if (!$type instanceof ReflectionNamedType) {
    //        throw new LogicException("Listener {$subscriberClass}::{$method} must have a typed parameter, or use the event attribute to explicitly define the event class");
    //    }
    //
    //    $type = $type->getName();
    //
    //    if (!class_exists($type)) {
    //        throw new LogicException("The type $type is not a valid event class");
    //    }
    //
    //    return $type;
    //}
}
