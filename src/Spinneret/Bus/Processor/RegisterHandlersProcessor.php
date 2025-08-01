<?php

namespace Arakne\Spinneret\Bus\Processor;

use Arakne\Spinneret\Bus\Attribute\MessageHandler;
use Arakne\Spinneret\Bus\BusDispatcher;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

use function assert;
use function class_exists;
use function count;

/**
 * Register handlers tagged with the {@see MessageHandler} tag.
 * If the "message" attribute is not provided, the message class is resolved from the handler parameter type.
 */
final readonly class RegisterHandlersProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    public function process(ContainerBuilder $builder): void
    {
        $handlers = [];
        $busDispatcher = $builder->services[BusDispatcher::class];

        foreach ($builder->findByTag(MessageHandler::class) as $service => $tags) {
            $service->public = true;
            $resolved = false;

            foreach ($tags as $tag) {
                assert($tag instanceof MessageHandler);

                $messageClass = $tag->message ?? $this->resolveMessageClass($service);
                $handlers[$messageClass] = $service->id;
                $resolved = true;
            }

            if (!$resolved) {
                $messageClass = $this->resolveMessageClass($service);
                $handlers[$messageClass] = $service->id;
            }
        }

        $busDispatcher->arguments[1] = $handlers;
    }

    /**
     * @param ServiceBuilder $service
     * @return class-string
     *
     * @throws ReflectionException
     */
    public function resolveMessageClass(ServiceBuilder $service): string
    {
        /** @var class-string $handlerClass */
        $handlerClass = $service->class ?? $service->id;

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
