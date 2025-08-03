<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\Autowire;
use Arakne\Spinneret\Container\Exception\MissingArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AutowireTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Autowire argument cannot be resolved. Please provide a service ID or a parameter name. Given: serviceId=Arakne\Tests\Spinneret\Container\Value\AutowireTest, parameterName=resolve');

        new Autowire(AutowireTest::class, 'resolve')->resolve($this->createMock(ContainerInterface::class));
    }

    #[Test]
    public function compile()
    {
        $this->expectException(MissingArgumentException::class);
        $this->expectExceptionMessage('Autowire argument cannot be resolved. Please provide a service ID or a parameter name. Given: serviceId=Arakne\Tests\Spinneret\Container\Value\AutowireTest, parameterName=resolve');

        new Autowire(AutowireTest::class, 'resolve')->compile();
    }

    #[Test]
    public function type()
    {
        $this->assertNull(new Autowire()->type());
    }
}
