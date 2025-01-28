<?php

namespace Arakne\Spinneret\Event;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
