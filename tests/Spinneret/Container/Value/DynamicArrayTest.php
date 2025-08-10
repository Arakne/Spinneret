<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\Reference;
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
        $builder->register('bar')->class(SimpleClass::class)->public();
        $container = $builder->build();

        $this->assertSame(['foo' => $container->get('bar')], $arg->resolve($container));
    }

    #[Test]
    public function type()
    {
        $this->assertSame('array', new DynamicArray(['foo', 'bar'])->type());
    }

    #[Test]
    public function traverseRead()
    {
        $value = new DynamicArray([
            'foo' => new Reference('bar'),
            'bar',
            new Literal(85),
        ]);

        $this->assertEquals([new Reference('bar'), new Literal(85)], iterator_to_array($value->traverse()));
    }

    #[Test]
    public function traverseWrite()
    {
        $value = new DynamicArray([
            'foo' => new Reference('bar'),
            'bar',
            new Literal(85),
        ]);

        $generator = $value->traverse();

        while ($generator->valid()) {
            $generator->send(new Literal('******'));
        }

        $newValue = $generator->getReturn();

        $this->assertInstanceOf(DynamicArray::class, $newValue);
        $this->assertEquals([
            'foo' => new Literal('******'),
            'bar', new Literal('******'),
        ], $newValue->values);
        $this->assertNotEquals($value, $newValue);
    }
}
