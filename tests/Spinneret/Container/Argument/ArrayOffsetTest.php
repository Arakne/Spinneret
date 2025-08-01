<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Argument\ArrayOffset;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
