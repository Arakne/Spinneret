<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StaticMethodServiceFactoryTest extends TestCase
{
    #[Test]
    public function create()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $instance = $factory->create($this->createMock(ContainerInterface::class), ['test']);

        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('TEST', $instance->value);
    }

    #[Test]
    public function createWithValueInterface()
    {
        $factory = new StaticMethodServiceFactory(new Literal(StaticFactory::class), 'create');
        $instance = $factory->create($this->createMock(ContainerInterface::class), ['test']);

        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('TEST', $instance->value);
    }

    #[Test]
    public function parameters()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $parameters = $factory->parameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('string', $parameters[0]->getType()->getName());

        $this->assertNull(new StaticMethodServiceFactory(StaticFactory::class, 'notFoundMethod')->parameters());
    }

    #[Test]
    public function parametersWithValueInterface()
    {
        $factory = new StaticMethodServiceFactory(new Literal(StaticFactory::class), 'create');
        $this->assertNull($factory->parameters());
    }

    #[Test]
    public function compile()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $arguments = '"test"';
        $compiled = $factory->compile($arguments);

        $this->assertSame('\Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory::create("test")', $compiled);
    }

    #[Test]
    public function compileWithValueInterface()
    {
        $factory = new StaticMethodServiceFactory(new PropertyAccess(new Reference('foo'), 'bar'), 'create');
        $arguments = '"test"';
        $compiled = $factory->compile($arguments);

        $this->assertSame('\$this->get(\'foo\')->bar::create("test")', $compiled);
    }

    #[Test]
    public function call()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $value = $factory->call(['test']);

        $this->assertEquals(new Call($factory, ['test']), $value);
    }

    #[Test]
    public function validateReturnsTrueIfClassAndMethodExist()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $builder = new ContainerBuilder();
        $this->assertTrue($factory->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfClassDoesNotExist()
    {
        $factory = new StaticMethodServiceFactory('NotAClass', 'create');
        $builder = new ContainerBuilder();
        $this->assertFalse($factory->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfMethodDoesNotExist()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'notFoundMethod');
        $builder = new ContainerBuilder();
        $this->assertFalse($factory->validate($builder));
    }

    #[Test]
    public function validateWithValueInterface()
    {
        $factory = new StaticMethodServiceFactory(new Literal(StaticFactory::class), 'method');
        $builder = new ContainerBuilder();
        $this->assertTrue($factory->validate($builder));
    }
}
