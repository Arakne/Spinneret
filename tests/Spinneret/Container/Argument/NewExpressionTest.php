<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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
}
