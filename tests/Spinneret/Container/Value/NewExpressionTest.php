<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function iterator_to_array;

class NewExpressionTest extends TestCase
{
    #[Test]
    public function resolveList()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo', 42],
        );

        $this->assertEquals(new ClassWithLiteralArguments('foo', 42), $arg->resolve($this->createMock(ContainerInterface::class)));
    }

    #[Test]
    public function resolveAssociative()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo' => 'foo', 'bar' => 42],
        );

        $this->assertEquals(new ClassWithLiteralArguments('foo', 42), $arg->resolve($this->createMock(ContainerInterface::class)));
    }

    #[Test]
    public function compileList()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo', 42],
        );

        $this->assertSame('new \Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments(\'foo\', 42)', $arg->compile());
    }

    #[Test]
    public function compileWithArrayParameter()
    {
        $arg = new NewExpression(
            className: ArrayObject::class,
            arguments: [[new Reference(SimpleClass::class)]],
        );

        $this->assertSame('new \ArrayObject([$this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\'), ])', $arg->compile());
    }

    #[Test]
    public function compileAssociative()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo' => 'foo', 'bar' => 42],
        );

        $this->assertSame('new \Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments(foo: \'foo\', bar: 42)', $arg->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertSame(ClassWithLiteralArguments::class, new NewExpression(ClassWithLiteralArguments::class)->type());
    }

    #[Test]
    public function traverseRead()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo', new Call(time(...))],
        );

        $this->assertEquals([new Call(time(...))], iterator_to_array($arg->traverse()));
    }

    #[Test]
    public function traverseWithArrayParameter()
    {
        $arg = new NewExpression(
            className: ArrayObject::class,
            arguments: [[new Reference(SimpleClass::class)]],
        );

        $this->assertEquals([new DynamicArray([new Reference(SimpleClass::class)])], iterator_to_array($arg->traverse()));
    }

    #[Test]
    public function traverseWrite()
    {
        $arg = new NewExpression(
            className: ClassWithLiteralArguments::class,
            arguments: ['foo', new Call(time(...))],
        );

        $generator = $arg->traverse();
        $generator->send(new Literal(155));

        $newValue = $generator->getReturn();
        $this->assertInstanceOf(NewExpression::class, $newValue);
        $this->assertEquals(['foo', new Literal(155)], $newValue->arguments);
        $this->assertSame(ClassWithLiteralArguments::class, $newValue->className);
        $this->assertNotEquals($arg, $newValue);
    }
}
