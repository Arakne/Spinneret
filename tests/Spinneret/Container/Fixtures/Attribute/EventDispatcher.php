<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures\Attribute;

readonly class EventDispatcher
{
    public function __construct(
        public array $listeners = [],
    ) {}

    public function dispatch(string $event, mixed $payload): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }
}
