<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

use Arakne\Spinneret\Event\Attribute\EventListener;

#[EventListener]
class SimpleEventListener
{
    public function __invoke(SimpleEvent $event): void
    {
        $event->handled = true;
        $event->listeners[] = __METHOD__;
    }
}

