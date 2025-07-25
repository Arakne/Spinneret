<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Argument\DynamicArray;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DynamicArrayTest extends TestCase
{
    #[Test]
    public function compileList()
    {
        $arg = new DynamicArray(['foo', new Reference('bar')]);
        $this->assertSame('[\'foo\', $this->get(\'bar\'), ]', $arg->compile());
    }

    #[Test]
    public function compileAssoc()
    {
        $arg = new DynamicArray(['foo' => new Reference('bar')]);
        $this->assertSame('[\'foo\' => $this->get(\'bar\'), ]', $arg->compile());
    }

    #[Test]
    public function resolve()
    {
        $arg = new DynamicArray(['foo' => new Reference('bar')]);
        $builder = new ContainerBuilder();
        $builder->register('bar')->class(SimpleClass::class);
        $container = $builder->build();

        $this->assertSame(['foo' => $container->get('bar')], $arg->resolve($container));
    }

    #[Test]
    public function type()
    {
        $this->assertSame('array', new DynamicArray(['foo', 'bar'])->type());
    }
}
