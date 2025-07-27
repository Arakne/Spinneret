<?php

namespace Arakne\Tests\Spinneret\Container;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class BuiltContainerTest extends TestCase
{
    #[Test]
    public function getContainer()
    {
        $builder = new ContainerBuilder();
        $built = $builder->build();

        $this->assertTrue($built->has(ContainerInterface::class));
        $this->assertSame($built, $built->get(ContainerInterface::class));
    }
}
