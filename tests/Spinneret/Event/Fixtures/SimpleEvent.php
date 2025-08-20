<?php

namespace Arakne\Tests\Spinneret\Event\Fixtures;

class SimpleEvent
{
    public bool $handled = false;
    public array $listeners = [];
}

