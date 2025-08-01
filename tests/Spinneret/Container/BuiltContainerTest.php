<?php

namespace Arakne\Tests\Spinneret\Container;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

class BuiltContainerTest extends TestCase
{
    #[Test]
    public function getContainer()
    {
        $builder = new ContainerBuilder();
        $built = $builder->build();

        $this->assertTrue($built->has(ContainerInterface::class));
        $this->assertSame($built, $built->get(ContainerInterface::class));
        $this->assertTrue($built->has(SpinneretContainerInterface::class));
        $this->assertSame($built, $built->get(SpinneretContainerInterface::class));
    }

    #[Test]
    public function set()
    {
        $builder = new ContainerBuilder();
        $built = $builder->build();

        $built->set('foo', $o = new stdClass());
        $this->assertTrue($built->has('foo'));
        $this->assertSame($o, $built->get('foo'));
    }
}
