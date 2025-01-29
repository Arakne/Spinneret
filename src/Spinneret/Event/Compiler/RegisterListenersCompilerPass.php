<?php

namespace Arakne\Spinneret\Event\Compiler;

use Arakne\Spinneret\Event\EventDispatcher;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function class_exists;
use function count;

/**
 * Register listeners tagged with the "spinneret.event.listener" tag.
 * If the "event" attribute is not provided, the event class is resolved from the handler parameter type.
 */
final readonly class RegisterListenersCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'spinneret.event.listener';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(EventDispatcher::class);
        /** @var array<string, list<string>> $listeners */
        $listeners = $definition->getArgument(1);

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            /** @var class-string $messageClass */
            $messageClass = $tags[0]['event'] ?? $this->resolveEventClass($container, $id);
            $listeners[$messageClass][] = $id;
            $container->getDefinition($id)->setPublic(true);
        }

        $definition->setArgument(1, $listeners);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveEventClass(ContainerBuilder $container, string $id): string
    {
        /** @var class-string $listenerClass */
        $listenerClass = $container->getDefinition($id)->getClass() ?? $id;

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
