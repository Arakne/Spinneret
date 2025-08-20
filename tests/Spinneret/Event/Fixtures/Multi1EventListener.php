<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

use Arakne\Spinneret\Event\Attribute\EventListener;

#[EventListener]
class Multi1EventListener
{
    public function __invoke(SimpleEvent $event): void
    {
        $event->listeners[] = __METHOD__;
    }
}

