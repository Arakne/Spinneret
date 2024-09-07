<?php

namespace Arakne\Spinneret\Bus;

use Exception;
use LogicException;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Base implementation of command bus dispatcher.
 * Will resolve the handler class from simple associative array, and create the handler instance from the container.
 */
final readonly class BusDispatcher implements BusDispatcherInterface
{
    public function __construct(
        private ContainerInterface $container,

        /**
         * Map of message class to handler class.
         * The handler class must implement the __invoke method, and take the message as argument.
         *
         * @var array<class-string, class-string>
         */
        private array $handlers,

        /**
         * Logger to use.
         * It's advisable to use one, if not all errors from {@see BusDispatcherInterface::dispatch()} will be silenced.
         *
         * Message logged are:
         * - when a message is dispatched, with the message class and handler class, in debug level
         * - when a message is processed, with the message class and handler result, in debug level
         * - when an error occurs on dispatch method, with the message class, the handler class and the exception, in error level
         */
        private ?LoggerInterface $logger = null,
    ) {
    }

    #[Override]
    public function dispatch(object $message): void
    {
        $messageClass = $message::class;
        $handler = $this->handler($messageClass);
        $handlerClass = $handler::class;

        $this->logger?->debug("Dispatching message $messageClass to handler $handlerClass", ['message' => $message]);

        try {
            $handler($message);
        } catch (Exception $e) {
            $this->logger?->error("Error while dispatching message $messageClass to handler $handlerClass : $e", ['message' => $message, 'exception' => $e]);
        }
    }

    #[Override]
    public function process(object $message, callable $process): mixed
    {
        $messageClass = $message::class;
        $handler = $this->handler($messageClass);
        $handlerClass = $handler::class;

        $this->logger?->debug("Dispatching message $messageClass to handler $handlerClass", ['message' => $message]);

        /** @psalm-suppress MixedAssignment */
        $res = $handler($message);

        $this->logger?->debug("Message $messageClass processed by handler $handlerClass", ['message' => $message, 'result' => $res]);

        /** @psalm-suppress MixedArgument */
        return $process($res);
    }

    /**
     * @param class-string<M> $messageClass
     * @return object&callable(M):mixed
     *
     * @template M as object
     */
    private function handler(string $messageClass): callable
    {
        $handlerClassName = $this->handlers[$messageClass] ?? throw new LogicException("No handler for message $messageClass");

        /** @var object&callable(M):mixed */
        return $this->container->get($handlerClassName);
    }
}
