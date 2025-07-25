<?php

namespace Arakne\Tests\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FunctionServiceFactoryTest extends TestCase
{
    #[Test]
    public function create()
    {
        $factory = new FunctionServiceFactory(
            function (string $name): string {
                return "Hello, $name!";
            }
        );

        $result = $factory->create($this->createMock(ContainerInterface::class), ['World']);
        $this->assertSame('Hello, World!', $result);
    }

    #[Test]
    public function parameters()
    {
        $factory = new FunctionServiceFactory(
            function (string $name): string {
                return "Hello, $name!";
            }
        );
        $parameters = $factory->parameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('string', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function compile()
    {
        $factory = new FunctionServiceFactory('strtoupper');
        $arguments = '"Hello, World!"';

        $compiled = $factory->compile($arguments);
        $this->assertSame('\strtoupper("Hello, World!")', $compiled);
    }

    #[Test]
    public function compileDoNotSupportClosure()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Cannot compile a function that is not a string.');

        $factory = new FunctionServiceFactory(
            function (string $name): string {
                return "Hello, $name!";
            }
        );
        $factory->compile('"World"');
    }
}
