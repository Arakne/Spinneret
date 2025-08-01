<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Argument\PropertyAccess;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PropertyAccessTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;
        $builder->register(SingleLiteralClass::class)
            ->arg(new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'foo'))
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertSame('test', $container->get(SingleLiteralClass::class)->value);
    }

    #[Test]
    public function compile()
    {
        $propertyAccess = new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'test');
        $this->assertSame('$this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ClassWithLiteralArguments\')->test', $propertyAccess->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertNull(new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'test')->type());
        $this->assertNull(new PropertyAccess(new Reference('not_a_class'), 'test')->type());
        $this->assertSame('int', new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'bar')->type());
    }
}
