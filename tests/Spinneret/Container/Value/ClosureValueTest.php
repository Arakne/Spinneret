<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\ClosureValue;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class ClosureValueTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class)->public();
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

    #[Test]
    public function traverseRead()
    {
        $value = new ClosureValue(new Reference(SimpleClass::class));
        $this->assertEquals([new Reference(SimpleClass::class)], iterator_to_array($value->traverse()));
    }

    #[Test]
    public function traverseWrite()
    {
        $value = new ClosureValue(new Reference(SimpleClass::class));

        $it = $value->traverse();
        $it->send(new NewExpression(SimpleClass::class));

        $newValue = $it->getReturn();

        $this->assertInstanceOf(ClosureValue::class, $newValue);
        $this->assertEquals(new NewExpression(SimpleClass::class), $newValue->value);
        $this->assertNotEquals($value, $newValue);
    }
}
