<?php

namespace Arakne\Tests\Spinneret\Container\Fixtures;

class FactoryWithDependency
{
    public function create(SimpleClass $class): ContainerClass
    {
        return new ContainerClass(
            $class,
            new ClassWithLiteralArguments('literal', 42)
        );
    }
}
