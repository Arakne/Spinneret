<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function trim;

class ServiceFactoryConverterTest extends TestCase
{
    #[Test]
    public function convertAlreadyFactory()
    {
        $factory = new FunctionServiceFactory('trim');
        $this->assertSame($factory, ServiceFactoryConverter::convert($factory));
    }

    #[Test]
    public function convertStringFunction()
    {
        $factory = ServiceFactoryConverter::convert('trim');
        $this->assertInstanceOf(FunctionServiceFactory::class, $factory);
        $this->assertSame('trim', $factory->function);
    }

    #[Test]
    public function convertStaticFCC()
    {
        $factory = ServiceFactoryConverter::convert(StaticFactory::create(...));
        $this->assertInstanceOf(StaticMethodServiceFactory::class, $factory);
        $this->assertSame(StaticFactory::class, $factory->class);
        $this->assertSame('create', $factory->method);
    }

    #[Test]
    public function convertMethodFCC()
    {
        $factoryObject = new InstanceFactory('test');
        $factory = ServiceFactoryConverter::convert($factoryObject->create(...));
        $this->assertInstanceOf(MethodServiceFactory::class, $factory);
        $this->assertEquals(new Literal($factoryObject), $factory->object);
        $this->assertSame('create', $factory->method);
    }

    #[Test]
    public function convertFunctionFCC()
    {
        $factory = ServiceFactoryConverter::convert(trim(...));
        $this->assertInstanceOf(FunctionServiceFactory::class, $factory);
        $this->assertSame('trim', $factory->function);
    }

    #[Test]
    public function convertAnonymousClosure()
    {
        $factory = ServiceFactoryConverter::convert(
            $closure = static fn (string $value): string => $value
        );

        $this->assertInstanceOf(FunctionServiceFactory::class, $factory);
        $this->assertSame($closure, $factory->function);
    }
}
