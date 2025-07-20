<?php

namespace Arakne\Spinneret\Application\Attribute;

use Arakne\Spinneret\Application\AbstractModule;
use ReflectionClass;

interface ModuleAttributeInterface
{
    public function register(ReflectionClass $class, AbstractModule $module): void;
}
