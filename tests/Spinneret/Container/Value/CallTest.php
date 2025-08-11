<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\PropertyAccess;
use Arakne\Spinneret\Container\Value\Reference;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_filter;
use function strtoupper;
use function var_dump;

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
    public function compileArrayArgument()
    {
        $call = new Call(
            array_filter(...),
            [
                ['foo', new Reference(SimpleClass::class)],
            ],
        );
        $this->assertSame('\array_filter([\'foo\', $this->get(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass\'), ])', $call->compile());
    }

    #[Test]
    public function compileLiteralArguments()
    {
        $call = new Call(
            strtoupper(...),
            ['test'],
        );
        $this->assertSame('\strtoupper(\'test\')', $call->compile());
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

    #[Test]
    public function traverseRead()
    {
        $call = new Call(
            new InstanceFactory('foo')->create(...),
            [new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value'), 78, [new Reference('id')]],
        );

        $this->assertEquals(
            [
                new Literal(new InstanceFactory('foo')),
                new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value'),
                new DynamicArray([new Reference('id')]),
            ],
            iterator_to_array($call->traverse()),
        );
    }

    #[Test]
    public function traverseWrite()
    {
        $call = new Call(
            new InstanceFactory('foo')->create(...),
            [new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value'), 78],
        );

        $generator = $call->traverse();

        $this->assertEquals(new Literal(new InstanceFactory('foo')), $generator->current());
        $generator->send(new Reference(InstanceFactory::class));

        $this->assertEquals(new PropertyAccess(new Literal((object) ['value' => 'bar']), 'value'), $generator->current());
        $generator->send(new Literal('baz'));

        $newCall = $generator->getReturn();

        $this->assertInstanceOf(Call::class, $newCall);
        $this->assertEquals(new MethodServiceFactory(new Reference(InstanceFactory::class), 'create'), $newCall->function);
        $this->assertEquals([new Literal('baz'), 78], $newCall->arguments);
        $this->assertNotEquals($call, $newCall);
    }

    #[Test]
    public function validateReturnsTrueIfFactoryAndArgumentsAreValid()
    {
        $call = new Call(strtoupper(...), ['foo']);
        $builder = new ContainerBuilder();
        $this->assertTrue($call->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfFactoryIsInvalid()
    {
        $call = new Call(new FunctionServiceFactory('not_callable'), []);
        $builder = new ContainerBuilder();
        $this->assertFalse($call->validate($builder));
    }

    #[Test]
    public function validateReturnsFalseIfArgumentsAreInvalid()
    {
        $call = new Call(strtoupper(...), [new Reference('invalid')]);
        $builder = new ContainerBuilder();
        $this->assertFalse($call->validate($builder));
    }
}
