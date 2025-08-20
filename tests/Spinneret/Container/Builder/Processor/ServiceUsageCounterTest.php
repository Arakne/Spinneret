<?php

namespace Arakne\Tests\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ServiceUsageCounter;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use ArrayObject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceUsageCounterTest extends TestCase
{
    #[Test]
    public function test()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class)->public();
        $builder->register(ClassWithLiteralArguments::class, ['foo', 42]);
        $builder->register(SingleLiteralClass::class)->factory(StaticFactory::create(...))->arg('test');
        $builder->register(ArrayObject::class, [[
            new Reference(SimpleClass::class),
            [
                'foo' => new Reference(ClassWithLiteralArguments::class),
                'bar' => new Reference(SingleLiteralClass::class),
            ],
            new Reference('foo'),
        ]])->public();
        $builder->register('foo')->value(new Reference(SingleLiteralClass::class)->property('value'));

        $counter = ServiceUsageCounter::fromContainerBuilder($builder);

        $this->assertSame([
            SimpleClass::class => ServiceUsageCounter::PUBLIC,
            ClassWithLiteralArguments::class => 1,
            SingleLiteralClass::class => 2,
            'foo' => 1,
        ], $counter->services);

        $this->assertFalse($counter->unused(SimpleClass::class));
        $this->assertFalse($counter->unused(ClassWithLiteralArguments::class));
        $this->assertFalse($counter->unused(SingleLiteralClass::class));
        $this->assertTrue($counter->unused(ContainerClass::class));
    }
}
