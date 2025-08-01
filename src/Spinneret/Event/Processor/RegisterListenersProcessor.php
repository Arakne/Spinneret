<?php

namespace Arakne\Spinneret\Event\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Event\EventDispatcher;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

use function class_exists;
use function count;

/**
 * Register listeners tagged with the "spinneret.event.listener" tag.
 * If the "event" attribute is not provided, the event class is resolved from the handler parameter type.
 *
 * @todo migrate
 */
final readonly class RegisterListenersProcessor implements ContainerBuilderProcessorInterface
{
    public const string TAG = 'spinneret.event.listener';

    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        //$definition = $builder->getDefinition(EventDispatcher::class);
        ///** @var array<string, list<string>> $listeners */
        //$listeners = $definition->getArgument(1);
        //
        //foreach ($builder->findTaggedServiceIds(self::TAG) as $id => $tags) {
        //    /** @var class-string $messageClass */
        //    $messageClass = $tags[0]['event'] ?? $this->resolveEventClass($builder, $id);
        //    $listeners[$messageClass][] = $id;
        //    $builder->getDefinition($id)->setPublic(true);
        //}
        //
        //$definition->setArgument(1, $listeners);
    }

    /**
     * @param ContainerBuilder $builder
     * @param string $id
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveEventClass(ContainerBuilder $builder, string $id): string
    {
        /** @var class-string $listenerClass */
        $listenerClass = $builder->getDefinition($id)->getClass() ?? $id;

        if (!method_exists($listenerClass, '__invoke')) {
            throw new LogicException("Listener $listenerClass must have an __invoke method");
        }

        $reflection = new ReflectionMethod($listenerClass, '__invoke');
        $parameters = $reflection->getParameters();

        if (count($parameters) !== 1) {
            throw new LogicException("Listener $listenerClass must have exactly one parameter");
        }

        $type = $parameters[0]->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Listener $listenerClass must have a typed parameter, or use the event attribute to explicitly define the event class");
        }

        $type = $type->getName();

        if (!class_exists($type)) {
            throw new LogicException("The type $type is not a valid event class");
        }

        return $type;
    }
}
