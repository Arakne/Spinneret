<?php

namespace Arakne\Spinneret\Bus\Compiler;

use Arakne\Spinneret\Bus\BusDispatcher;
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
 * Register handlers tagged with the "spinneret.bus.handler" tag.
 * If the "message" attribute is not provided, the message class is resolved from the handler parameter type.
 */
final readonly class RegisterHandlersCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'spinneret.bus.handler';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $handlers = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            /** @var class-string $messageClass */
            $messageClass = $tags[0]['message'] ?? $this->resolveMessageClass($container, $id);
            $handlers[$messageClass] = $id;
            $container->getDefinition($id)->setPublic(true);
        }

        $container->getDefinition(BusDispatcher::class)->setArgument(1, $handlers);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveMessageClass(ContainerBuilder $container, string $id): string
    {
        /** @var class-string $handlerClass */
        $handlerClass = $container->getDefinition($id)->getClass() ?? $id;

        if (!method_exists($handlerClass, '__invoke')) {
            throw new LogicException("Handler $handlerClass must have an __invoke method");
        }

        $reflection = new ReflectionMethod($handlerClass, '__invoke');
        $parameters = $reflection->getParameters();

        if (count($parameters) !== 1) {
            throw new LogicException("Handler $handlerClass must have exactly one parameter");
        }

        $type = $parameters[0]->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Handler $handlerClass must have a typed parameter, or use the message attribute to explicitly define the message class");
        }

        $type = $type->getName();

        if (!class_exists($type)) {
            throw new LogicException("The type $type is not a valid message class");
        }

        return $type;
    }
}
