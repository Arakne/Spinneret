<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Value\ClosureValue;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClosureArgumentTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $container = $builder->build();

        $value = new ClosureValue(new Reference(SimpleClass::class));

        $this->assertInstanceOf(Closure::class, $value->resolve($container));
        $this->assertSame($container->get(SimpleClass::class), $value->resolve($container)());
    }

    #[Test]
    public function compile()
    {
        $value = new ClosureValue(new Reference(SimpleClass::class));

        $this->assertSame('(fn () => $this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\'))', $value->compile());
    }

    #[Test]
    public function type()
    {
        $value = new ClosureValue(new Reference(SimpleClass::class));
        $this->assertSame(Closure::class, $value->type());
    }
}
