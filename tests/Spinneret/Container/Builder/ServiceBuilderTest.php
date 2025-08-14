<?php

namespace Arakne\Tests\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\PrivateConstructorClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use ArrayAccess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceBuilderTest extends TestCase
{
    #[Test]
    public function asInlineValueWithClass()
    {
        $service = new ServiceBuilder('', SingleLiteralClass::class);
        $service->arg('foo');

        $value = $service->asInlineValue(new ContainerBuilder());

        $this->assertEquals(new NewExpression(SingleLiteralClass::class, [new Literal('foo')]), $value);
    }

    #[Test]
    public function asInlineValueWithNoClassButWithFactory()
    {
        $service = new ServiceBuilder('', null);
        $service->factory(StaticFactory::create(...));
        $service->arg('foo');

        $value = $service->asInlineValue(new ContainerBuilder());

        $this->assertEquals(
            new Call(new StaticMethodServiceFactory(StaticFactory::class, 'create'), [new Literal('foo')]),
            $value
        );
    }

    #[Test]
    public function asInlineValueWithNoClassAndNoFactory()
    {
        $service = new ServiceBuilder('', null);

        $value = $service->asInlineValue(new ContainerBuilder());

        $this->assertNull($value);
    }

    #[Test]
    public function validateReturnsTrueIfRuntime()
    {
        $builder = new ServiceBuilder('id', SingleLiteralClass::class);
        $builder->runtime = true;
        $containerBuilder = new ContainerBuilder();
        $this->assertTrue($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfNoClassAndNoFactory()
    {
        $builder = new ServiceBuilder('id', null);
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfClassIsNotInstantiable()
    {
        $builder = new ServiceBuilder('id', ArrayAccess::class);
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfConstructorIsNotPublic()
    {
        $builder = new ServiceBuilder('id', PrivateConstructorClass::class);
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfNotEnoughArguments()
    {
        $builder = new ServiceBuilder('id', SingleLiteralClass::class);
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfArgumentIsInvalid()
    {
        $builder = new ServiceBuilder('id', SingleLiteralClass::class);
        $builder->arguments = [new Reference('invalid')];
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsFalseIfFactoryIsInvalid()
    {
        $invalidFactory = new MethodServiceFactory(new Reference('invalid'), 'method');

        $builder = new ServiceBuilder('id', null);
        $builder->factory($invalidFactory);
        $containerBuilder = new ContainerBuilder();
        $this->assertFalse($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsTrueIfValidClass()
    {
        $builder = new ServiceBuilder('id', SingleLiteralClass::class);
        $builder->arguments = ['foo'];
        $containerBuilder = new ContainerBuilder();
        $this->assertTrue($builder->validate($containerBuilder));
    }

    #[Test]
    public function validateReturnsTrueIfValidFactory()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $builder = new ServiceBuilder('id', null);
        $builder->factory($factory);
        $builder->arguments = ['foo'];
        $containerBuilder = new ContainerBuilder();
        $this->assertTrue($builder->validate($containerBuilder));
    }
}
