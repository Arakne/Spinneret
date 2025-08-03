<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\ArrayOffset;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class ArrayOffsetTest extends TestCase
{
    #[Test]
    public function resolveFunctional()
    {
        $builder = new ContainerBuilder();
        $builder->register('config')
            ->class(ArrayObject::class)
            ->arg(['foo' => 'bar', 'baz' => 42])
        ;
        $container = $builder->build();

        $value = new ArrayOffset(new Reference('config'), 'foo');
        $this->assertSame('bar', $value->resolve($container));
    }

    #[Test]
    public function compile()
    {
        $value = new ArrayOffset(new Reference('config'), 'foo');
        $this->assertSame('$this->get(\'config\')[\'foo\']', $value->compile());
    }

    #[Test]
    public function type()
    {
        $value = new ArrayOffset(new Reference('config'), 'foo');
        $this->assertNull($value->type());
    }

    #[Test]
    public function traverseRead()
    {
        $value = new ArrayOffset($ref = new Reference('config'), 'foo');
        $this->assertSame([$ref], iterator_to_array($value->traverse()));
    }

    #[Test]
    public function traverseWrite()
    {
        $value = new ArrayOffset(new Reference('config'), 'foo');

        $g = $value->traverse();

        while ($g->valid()) {
            $g->send(new Literal(['foo' => 42]));
        }

        $newValue = $g->getReturn();
        $this->assertNotEquals($value, $newValue);
        $this->assertInstanceOf(ArrayOffset::class, $newValue);
        $this->assertEquals(new Literal(['foo' => 42]), $newValue->array);
        $this->assertSame('foo', $newValue->offset);
    }
}
