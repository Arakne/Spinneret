<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Compiler\PhpClassContainerCompiler;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Value\FirstClassCallable;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Closure;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

use function strtoupper;
use function var_dump;

class FirstClassCallableTest extends TestCase
{
    #[Test]
    public function resolveWithClosure()
    {
        $closure = fn($x) => $x . '!';
        $fcc = new FirstClassCallable($closure);
        $resolved = $fcc->resolve($this->createMock(ContainerInterface::class));
        $this->assertInstanceOf(Closure::class, $resolved);
        $this->assertSame($closure, $resolved);
        $this->assertSame('foo!', $resolved('foo'));
    }

    #[Test]
    public function resolveWithServiceFactory()
    {
        $fcc = new FirstClassCallable(new FunctionServiceFactory('strtoupper'));
        $resolved = $fcc->resolve($this->createMock(ContainerInterface::class));
        $this->assertInstanceOf(Closure::class, $resolved);
        $this->assertSame('FOO', $resolved('foo'));
    }

    #[Test]
    public function resolveWithCallableString()
    {
        $fcc = new FirstClassCallable(strtoupper(...));
        $resolved = $fcc->resolve($this->createMock(ContainerInterface::class));
        $this->assertInstanceOf(Closure::class, $resolved);
        $this->assertSame('BAR', $resolved('bar'));
    }

    #[Test]
    public function compile()
    {
        $fcc = new FirstClassCallable(strtoupper(...));
        $this->assertSame('\strtoupper(...)', $fcc->compile());
    }

    #[Test]
    public function compileWithMethodServiceFactory()
    {
        $factory = new MethodServiceFactory(new Reference(SimpleClass::class), 'getValue');
        $fcc = new FirstClassCallable($factory);
        $this->assertSame('$this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\')->getValue(...)', $fcc->compile());
    }

    #[Test]
    public function type()
    {
        $fcc = new FirstClassCallable(strtoupper(...));
        $this->assertSame(Closure::class, $fcc->type());
    }

    #[Test]
    public function traverseRead()
    {
        $factory = new MethodServiceFactory(new Reference(SimpleClass::class), 'getValue');
        $fcc = new FirstClassCallable($factory);

        $traversed = iterator_to_array($fcc->traverse());
        $this->assertCount(1, $traversed);
        $this->assertEquals(new Reference(SimpleClass::class), $traversed[0]);
    }

    #[Test]
    public function traverseWrite()
    {
        $factory = new MethodServiceFactory(new Reference(SimpleClass::class), 'getValue');
        $fcc = new FirstClassCallable($factory);

        $generator = $fcc->traverse();
        $this->assertEquals(new Reference(SimpleClass::class), $generator->current());
        $generator->send(new Literal('foo'));

        $newFcc = $generator->getReturn();
        $this->assertInstanceOf(FirstClassCallable::class, $newFcc);
        $this->assertNotEquals($fcc, $newFcc);
        $this->assertInstanceOf(MethodServiceFactory::class, $newFcc->function);
        $this->assertEquals(new Literal('foo'), $newFcc->function->object);
    }

    #[Test]
    public function validateReturnsTrueIfFactoryIsValid()
    {
        $fcc = new FirstClassCallable(strtoupper(...));
        $builder = new ContainerBuilder();
        $this->assertTrue($fcc->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfFactoryIsInvalid()
    {
        $fcc = new FirstClassCallable(new FunctionServiceFactory('not_callable'));
        $builder = new ContainerBuilder();
        $this->assertFalse($fcc->validate($builder));
    }

    #[Test]
    public function resolveFunctionalClosure()
    {
        $builder = new ContainerBuilder();
        $builder->register(ArrayObject::class, [[new FirstClassCallable(strtoupper(...))]])->public();
        $container = $builder->build();

        $ao = $container->get(ArrayObject::class);
        $fcc = $ao[0];

        $this->assertInstanceOf(Closure::class, $fcc);
        $this->assertSame('HELLO', $fcc('hello'));
    }

    #[Test]
    public function resolveFunctionalFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(ArrayObject::class, [[
            new FirstClassCallable(
                ServiceFactoryConverter::convert(new Randomizer(new Xoshiro256StarStar(1))->getInt(...))
            )
        ]])->public();
        $container = $builder->build();

        $ao = $container->get(ArrayObject::class);
        $fcc = $ao[0];
        $this->assertInstanceOf(Closure::class, $fcc);
        $this->assertSame(8, $fcc(1, 10));
    }

    #[Test]
    public function resolveFunctionalCompiledClosure()
    {
        $builder = new ContainerBuilder();
        $builder->register(ArrayObject::class, [[new FirstClassCallable(strtoupper(...))]])->public();
        eval($builder->build()->compile(new PhpClassContainerCompiler('ResolveFunctionalCompiledClosureContainer')));
        $container = new \ResolveFunctionalCompiledClosureContainer();

        $ao = $container->get(ArrayObject::class);
        $fcc = $ao[0];

        $this->assertInstanceOf(Closure::class, $fcc);
        $this->assertSame('HELLO', $fcc('hello'));
    }

    #[Test]
    public function resolveFunctionalCompiledFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(Xoshiro256StarStar::class, [1]);
        $builder->register(Randomizer::class, [new Reference(Xoshiro256StarStar::class)]);
        $builder->register(ArrayObject::class, [[
            new Reference(Randomizer::class)->method('getInt')->fcc()
        ]])->public();
        eval($builder->build()->compile(new PhpClassContainerCompiler('ResolveFunctionalCompiledFactoryContainer')));
        $container = new \ResolveFunctionalCompiledFactoryContainer();

        $ao = $container->get(ArrayObject::class);
        $fcc = $ao[0];
        $this->assertInstanceOf(Closure::class, $fcc);
        $this->assertSame(8, $fcc(1, 10));
    }
}
