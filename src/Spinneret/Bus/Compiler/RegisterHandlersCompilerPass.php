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

final readonly class RegisterHandlersCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'spinneret.bus.handler';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $handlers = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $messageClass = $tags[0]['message'] ?? $this->resolveMessageClass($container, $id);
            $handlers[$messageClass] = $id;
            $container->getDefinition($id)->setPublic(true);
        }

        $container->getDefinition(BusDispatcher::class)->setArgument(1, $handlers);
    }

    /**
     * @param ContainerBuilder $container
     * @param class-string $id
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveMessageClass(ContainerBuilder $container, string $id): string
    {
        $handlerClass = $container->getDefinition($id)->getClass() ?? $id;
        $reflection = new ReflectionMethod($handlerClass, '__invoke');
        $parameters = $reflection->getParameters();

        if (count($parameters) !== 1) {
            throw new LogicException("Handler $handlerClass must have exactly one parameter");
        }

        $type = $parameters[0]->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Handler $handlerClass must have a typed parameter");
        }

        $type = $type->getName();

        if (!class_exists($type)) {
            throw new LogicException("The type $type is not a valid message class");
        }

        return $type;
    }
}
