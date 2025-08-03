<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

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

    #[Test]
    public function traverseRead()
    {
        $value = new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'test');

        $this->assertEquals([new Reference(ClassWithLiteralArguments::class)], iterator_to_array($value->traverse()));
    }

    #[Test]
    public function traverseWrite()
    {
        $value = new PropertyAccess(new Reference(ClassWithLiteralArguments::class), 'test');

        $generator = $value->traverse();
        $generator->send(new Literal(new ClassWithLiteralArguments('new_value', 42)));

        $newValue = $generator->getReturn();
        $this->assertInstanceOf(PropertyAccess::class, $newValue);
        $this->assertEquals(new Literal(new ClassWithLiteralArguments('new_value', 42)), $newValue->object);
        $this->assertSame('test', $newValue->property);
        $this->assertNotEquals($value, $newValue);
    }
}
