<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Value\ArrayOffset;
use Arakne\Spinneret\Container\Value\ClosureValue;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValueHelperTraitTest extends TestCase
{
    #[Test]
    public function method()
    {
        $value = new Reference('foo');
        $result = $value->method('bar');

        $this->assertInstanceOf(MethodServiceFactory::class, $result);
        $this->assertSame($value, $result->object);
        $this->assertSame('bar', $result->method);
    }

    #[Test]
    public function property()
    {
        $value = new Reference('foo');
        $result = $value->property('bar');

        $this->assertInstanceOf(PropertyAccess::class, $result);
        $this->assertSame($value, $result->object);
        $this->assertSame('bar', $result->property);
    }

    #[Test]
    public function offset()
    {
        $value = new Reference('foo');
        $result = $value->offset('bar');

        $this->assertInstanceOf(ArrayOffset::class, $result);
        $this->assertSame($value, $result->array);
        $this->assertSame('bar', $result->offset);
    }

    #[Test]
    public function asClosure()
    {
        $value = new Reference('foo');
        $result = $value->asClosure();

        $this->assertInstanceOf(ClosureValue::class, $result);
        $this->assertSame($value, $result->value);
    }
}
