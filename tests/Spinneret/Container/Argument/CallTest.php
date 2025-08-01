<?php

namespace Arakne\Tests\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Argument\Call;
use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\PropertyAccess;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CallTest extends TestCase
{
    #[Test]
    public function resolve()
    {
        $call = new Call(
            new InstanceFactory('foo')->create(...),
            [new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value')],
        );
        $this->assertEquals(new SingleLiteralClass('barfoo'), $call->resolve($this->createMock(ContainerInterface::class)));
    }

    #[Test]
    public function compile()
    {
        $call = new Call(
            new InstanceFactory('foo')->create(...),
            [new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value')],
        );
        $this->assertSame('new \Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory(\'foo\')->create(((object) [\'value\' => \'bar\', ])->value)', $call->compile());
    }

    #[Test]
    public function type()
    {
        $call = new Call(
            new InstanceFactory('foo')->create(...),
            [new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value')],
        );

        $this->assertNull($call->type());
    }
}
