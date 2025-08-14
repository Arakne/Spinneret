<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReferenceTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $ref = new Reference(SimpleClass::class);
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class)->public();
        $container = $builder->build();

        $this->assertSame($container->get(SimpleClass::class), $ref->resolve($container));
    }

    #[Test]
    public function resolveNullOnInvalid()
    {
        $ref = new Reference(SimpleClass::class, nullOnInvalid: true);
        $builder = new ContainerBuilder();
        $container = $builder->build();

        $this->assertNull($ref->resolve($container));

        $container->set(SimpleClass::class, new SimpleClass());
        $this->assertSame($container->get(SimpleClass::class), $ref->resolve($container));
    }

    #[Test]
    public function resolveDefaultValueOnInvalid()
    {
        $ref = new Reference(SimpleClass::class, defaultValueOnInvalid: 'default');
        $builder = new ContainerBuilder();
        $container = $builder->build();

        $this->assertSame('default', $ref->resolve($container));

        $container->set(SimpleClass::class, new SimpleClass());
        $this->assertSame($container->get(SimpleClass::class), $ref->resolve($container));
    }

    #[Test]
    public function compile()
    {
        $ref = new Reference(SimpleClass::class);
        $this->assertSame('$this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\')', $ref->compile());
    }

    #[Test]
    public function containerReference()
    {
        $this->assertSame('$this', new Reference(ContainerInterface::class)->compile());
        $this->assertSame('$this', new Reference(SpinneretContainerInterface::class)->compile());
    }

    #[Test]
    public function compileNullOnInvalid()
    {
        $ref = new Reference(SimpleClass::class, true);
        $this->assertSame('$this->getOrNull(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\')', $ref->compile());
    }

    #[Test]
    public function compileDefaultValueOnInvalid()
    {
        $ref = new Reference(SimpleClass::class, defaultValueOnInvalid: new SimpleClass());
        $this->assertSame('($this->getOrNull(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\') ?? new \Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass())', $ref->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertSame(SimpleClass::class, new Reference(SimpleClass::class)->type());
        $this->assertNull(new Reference('non.existent.Class')->type());
    }

    #[Test]
    public function method()
    {
        $ref = new Reference(SimpleClass::class);
        $this->assertEquals(new MethodServiceFactory($ref, 'methodName'), $ref->method('methodName'));
    }

    #[Test]
    public function property()
    {
        $ref = new Reference(SimpleClass::class);
        $this->assertEquals(new PropertyAccess($ref, 'prop'), $ref->property('prop'));
    }

    #[Test]
    public function validateReturnsTrueIfReferenceIsValid()
    {
        $builder = new ContainerBuilder();
        $builder->register('foo')->class(SimpleClass::class);
        $ref = new Reference('foo');
        $this->assertTrue($ref->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfReferenceIsInvalid()
    {
        $builder = new ContainerBuilder();
        $ref = new Reference('not_found');
        $this->assertFalse($ref->validate($builder));
    }

    #[Test]
    public function validateReturnsTrueIfNullOnInvalid()
    {
        $builder = new ContainerBuilder();
        $ref = new Reference('not_found', nullOnInvalid: true);
        $this->assertTrue($ref->validate($builder));
    }

    #[Test]
    public function validateReturnsTrueIfDefaultValueOnInvalid()
    {
        $builder = new ContainerBuilder();
        $ref = new Reference('not_found', defaultValueOnInvalid: 'default');
        $this->assertTrue($ref->validate($builder));
    }

    #[Test]
    public function validateContainerReference()
    {
        $this->assertTrue(new Reference(ContainerInterface::class)->validate(new ContainerBuilder()));
        $this->assertTrue(new Reference(SpinneretContainerInterface::class)->validate(new ContainerBuilder()));
    }
}
