<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\FactoryWithDependency;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MethodServiceFactoryTest extends TestCase
{
    #[Test]
    public function argumentsWithReferenceSuccess()
    {
        $factory = new MethodServiceFactory(new Reference(FactoryWithDependency::class), 'create');
        $parameters = $factory->parameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals(SimpleClass::class, $parameters[0]->getType()->getName());
    }

    #[Test]
    public function argumentsWithLiteralSuccess()
    {
        $factory = new MethodServiceFactory(new Literal(new FactoryWithDependency()), 'create');
        $parameters = $factory->parameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals(SimpleClass::class, $parameters[0]->getType()->getName());
    }

    #[Test]
    public function argumentsWithUndefinedType()
    {
        $factory = new MethodServiceFactory(new Reference('id'), 'create');
        $this->assertNull($factory->parameters());
    }

    #[Test]
    public function argumentsWithMethodNotFound()
    {
        $factory = new MethodServiceFactory(new Reference(FactoryWithDependency::class), 'invalid');
        $this->assertNull($factory->parameters());
    }

    #[Test]
    public function create()
    {
        $container = new ContainerBuilder()->build();
        $factory = new MethodServiceFactory(new Literal(new FactoryWithDependency()), 'create');

        $instance = $factory->create($container, [$simple = new SimpleClass()]);

        $this->assertInstanceOf(ContainerClass::class, $instance);
        $this->assertSame($simple, $instance->simpleClass);
        $this->assertEquals(new ClassWithLiteralArguments('literal', 42), $instance->classWithLiteralArguments);
    }

    #[Test]
    public function compile()
    {
        $factory = new MethodServiceFactory(new Reference('id'), 'create');
        $this->assertSame('$this->get(\'id\')->create($arguments)', $factory->compile('$arguments'));
    }
}
