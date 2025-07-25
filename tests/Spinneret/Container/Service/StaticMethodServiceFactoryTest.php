<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
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
    public function parameters()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $parameters = $factory->parameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('string', $parameters[0]->getType()->getName());

        $this->assertNull(new StaticMethodServiceFactory(StaticFactory::class, 'notFoundMethod')->parameters());
    }

    #[Test]
    public function compile()
    {
        $factory = new StaticMethodServiceFactory(StaticFactory::class, 'create');
        $arguments = '"test"';
        $compiled = $factory->compile($arguments);

        $this->assertSame('\Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory::create("test")', $compiled);
    }
}
