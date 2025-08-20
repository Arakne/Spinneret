<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

use Psr\EventDispatcher\StoppableEventInterface;

class StoppableEvent implements StoppableEventInterface
{
    public bool $handled = false;
    public array $listeners;
    private bool $stop = false;

    public function stopPropagation(): void
    {
        $this->stop = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }
}
