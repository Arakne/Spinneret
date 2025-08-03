<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class LiteralTest extends TestCase
{
    #[Test]
    public function dump()
    {
        $this->assertSame('null', Literal::dump(null));
        $this->assertSame('true', Literal::dump(true));
        $this->assertSame('false', Literal::dump(false));
        $this->assertSame('42', Literal::dump(42));
        $this->assertSame('1.23', Literal::dump(1.23));
        $this->assertSame("'foo'", Literal::dump('foo'));
        $this->assertSame("[]", Literal::dump([]));
        $this->assertSame("[1, 2, 3, ]", Literal::dump([1, 2, 3]));
        $this->assertSame("['foo' => 'bar', ]", Literal::dump(['foo' => 'bar']));
        $this->assertSame("((object) [])", Literal::dump(new stdClass()));
        $this->assertSame("((object) ['foo' => 'bar', ])", Literal::dump((object) ['foo' => 'bar']));
        $this->assertSame('new \Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass()', Literal::dump(new SimpleClass()));
        $this->assertSame("new \\Arakne\\Tests\\Spinneret\\Container\\Fixtures\\ClassWithLiteralArguments('test', 42)", Literal::dump(new ClassWithLiteralArguments('test', 42)));
        $this->assertSame("new \\Arakne\\Tests\\Spinneret\\Container\\Value\\WithOptionalArguments('foo')", Literal::dump(new WithOptionalArguments('foo', 'bar', 42)));
        $this->assertSame("new \\Arakne\\Tests\\Spinneret\\Container\\Value\\NullableRequiredArguments(null, null)", Literal::dump(new NullableRequiredArguments('foo', 42)));
    }

    #[Test]
    public function dumpUnsupportedObject()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Cannot dump object of class ' . UnsupportedObject::class);

        Literal::dump(new UnsupportedObject('foo', 42));
    }

    #[Test]
    public function dumpPrivateConstructor()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Cannot dump object of class Arakne\Tests\Spinneret\Container\Value\PrivateConstructor: constructor is not public.');

        Literal::dump(PrivateConstructor::create());
    }

    #[Test]
    public function dumpUnsupportedType()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Unsupported value type: resource');

        Literal::dump(STDIN);
    }

    #[Test]
    public function dumpEnum()
    {
        $this->assertSame('\Arakne\Tests\Spinneret\Container\Value\MyEnum::Foo', Literal::dump(MyEnum::Foo));
    }

    #[Test]
    public function dumpDateTimeZone()
    {
        $this->assertSame('new \DateTimeZone(\'Europe/Paris\')', Literal::dump(new \DateTimeZone('Europe/Paris')));
    }

    #[Test]
    public function resolve()
    {
        $this->assertSame(42, new Literal(42)->resolve($this->createMock(ContainerInterface::class)));
    }

    #[Test]
    public function compile()
    {
        $this->assertSame('42', new Literal(42)->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertSame('int', new Literal(42)->type());
        $this->assertSame('float', new Literal(1.23)->type());
        $this->assertSame('bool', new Literal(true)->type());
        $this->assertSame('null', new Literal(null)->type());
        $this->assertSame('string', new Literal('foo')->type());
        $this->assertSame('array', new Literal([])->type());
        $this->assertSame(stdClass::class, new Literal(new stdClass())->type());
        $this->assertSame(SimpleClass::class, new Literal(new SimpleClass())->type());
        $this->assertNull(new Literal(STDIN)->type());
    }
}

class WithOptionalArguments
{
    public function __construct(
        public string $foo,
        string $bar = 'default',
        ?int $baz = null,
    ) {}
}

class UnsupportedObject
{
    public function __construct(string $foo, int $bar) {}
}

class NullableRequiredArguments
{
    public function __construct(?string $foo, ?int $bar) {}
}

class PrivateConstructor
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }
}

enum MyEnum
{
    case Foo;
    case Bar;
}
