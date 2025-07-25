<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $ref = new Reference(SimpleClass::class);
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $container = $builder->build();

        $this->assertSame($container->get(SimpleClass::class), $ref->resolve($container));
    }

    #[Test]
    public function compile()
    {
        $ref = new Reference(SimpleClass::class);
        $this->assertSame('$this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\')', $ref->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertSame(SimpleClass::class, new Reference(SimpleClass::class)->type());
        $this->assertNull(new Reference('non.existent.Class')->type());
    }
}
