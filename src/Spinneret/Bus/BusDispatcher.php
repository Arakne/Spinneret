<?php

namespace Arakne\Spinneret\Bus;

use LogicException;
use Override;
use Psr\Container\ContainerInterface;

final readonly class BusDispatcher implements BusDispatcherInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $handlers,
    ) {
    }

    #[Override]
    public function dispatch(object $message): void
    {
        $this->handler($message::class)($message);
    }

    #[Override]
    public function process(object $message, callable $process): mixed
    {
        $res = $this->handler($message::class)($message);

        return $process($res);
    }

    /**
     * @param class-string<M> $messageClass
     * @return callable(M):mixed
     *
     * @template M as object
     */
    private function handler(string $messageClass): callable
    {
        $handlerClassName = $this->handlers[$messageClass] ?? throw new LogicException("No handler for message $messageClass");

        return $this->container->get($handlerClassName);
    }
}
