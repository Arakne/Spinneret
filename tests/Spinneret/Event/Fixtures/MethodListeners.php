<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

use Arakne\Spinneret\Event\Attribute\EventListener;

class MethodListeners
{
    #[EventListener]
    public function onSimpleEvent(SimpleEvent $event): void
    {
        $event->listeners[] = __METHOD__;
    }

    #[EventListener]
    public function onStoppableEvent(StoppableEvent $event): void
    {
        $event->listeners[] = __METHOD__;
    }

    #[EventListener]
    public function onMixedEvents(SimpleEvent|StoppableEvent $event): void
    {
        $event->listeners[] = __METHOD__;
    }

    #[EventListener(SimpleEvent::class)]
    public function onExplicitEventType($event): void
    {
        $event->listeners[] = __METHOD__;
    }
}
