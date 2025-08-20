<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

use Arakne\Spinneret\Event\Attribute\EventListener;

#[EventListener]
class StoppableEventListener
{
    public function __invoke(StoppableEvent $event): void
    {
        $event->handled = true;
        $event->listeners[] = __METHOD__;
        $event->stopPropagation();
    }
}

