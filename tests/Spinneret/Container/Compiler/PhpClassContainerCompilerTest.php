<?php

namespace Arakne\Tests\Spinneret\Container\Compiler;

use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\PropertyAccess;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Argument\TaggedServiceIterator;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Compiler\PhpClassContainerCompiler;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\MyTagInterface;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TagContainer;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TaggedA;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TaggedB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use Psr\Container\NotFoundExceptionInterface;

use function class_exists;

class PhpClassContainerCompilerTest extends TestCase
{
    #[Test]
    public function compileSimpleContainer()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $container = $builder->build();

        $compiled = $container->compile(new PhpClassContainerCompiler('SimpleContainerTest'));

        $this->assertStringContainsString('final class SimpleContainerTest implements \Psr\Container\ContainerInterface', $compiled);
        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass' => new \Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass(),", $compiled);

        eval($compiled);

        $this->assertTrue(class_exists('SimpleContainerTest'));
        $compiledContainer = new \SimpleContainerTest();

        $this->assertInstanceOf(ContainerInterface::class, $compiledContainer);

        $this->assertTrue($compiledContainer->has(SimpleClass::class));
        $this->assertFalse($compiledContainer->has('other'));

        $this->assertInstanceOf(SimpleClass::class, $compiledContainer->get(SimpleClass::class));
        $this->assertSame($compiledContainer->get(SimpleClass::class), $compiledContainer->get(SimpleClass::class));

        try {
            $compiledContainer->get('not_found');
            $this->fail('Container should not throw NotFoundException for existing service');
        } catch (NotFoundExceptionInterface $e) {
            $this->assertStringContainsString('Service "not_found" not found', $e->getMessage());
        }
    }

    #[Test]
    public function withLiteralArguments()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('foo')
            ->arg(45)
        ;
        $container = $builder->build();

        $compiled = $container->compile(new PhpClassContainerCompiler('LiteralArgContainerTest'));

        $this->assertStringContainsString('final class LiteralArgContainerTest implements \Psr\Container\ContainerInterface', $compiled);
        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ClassWithLiteralArguments' => new \Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments('foo', 45),", $compiled);

        eval($compiled);

        $this->assertTrue(class_exists('LiteralArgContainerTest'));
        $compiledContainer = new \LiteralArgContainerTest();

        $this->assertInstanceOf(ContainerInterface::class, $compiledContainer);

        $this->assertTrue($compiledContainer->has(ClassWithLiteralArguments::class));

        $this->assertInstanceOf(ClassWithLiteralArguments::class, $compiledContainer->get(ClassWithLiteralArguments::class));
        $this->assertSame($compiledContainer->get(ClassWithLiteralArguments::class), $compiledContainer->get(ClassWithLiteralArguments::class));
        $this->assertSame('foo', $compiledContainer->get(ClassWithLiteralArguments::class)->foo);
        $this->assertSame(45, $compiledContainer->get(ClassWithLiteralArguments::class)->bar);
    }

    #[Test]
    public function withReference()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('a')
            ->arg(1)
        ;
        $builder->register(ContainerClass::class);

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('ReferenceContainerTest'));

        eval($compiled);
        $compiledContainer = new \ReferenceContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ContainerClass' => new \Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass(\$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass'), \$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ClassWithLiteralArguments')),", $compiled);
        $instance = $compiledContainer->get(ContainerClass::class);
        $this->assertInstanceOf(ContainerClass::class, $instance);
        $this->assertSame($instance, $compiledContainer->get(ContainerClass::class));
        $this->assertInstanceOf(SimpleClass::class, $instance->simpleClass);
        $this->assertInstanceOf(ClassWithLiteralArguments::class, $instance->classWithLiteralArguments);
        $this->assertSame($compiledContainer->get(SimpleClass::class), $instance->simpleClass);
        $this->assertSame($compiledContainer->get(ClassWithLiteralArguments::class), $instance->classWithLiteralArguments);
        $this->assertSame('a', $instance->classWithLiteralArguments->foo);
        $this->assertSame(1, $instance->classWithLiteralArguments->bar);
    }

    #[Test]
    public function withPropertyAccess()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)->arg('a')->arg(1);
        $builder->register(SingleLiteralClass::class)->arg(new PropertyAccess(ClassWithLiteralArguments::class, 'foo'));

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('PropertyAccessContainerTest'));

        eval($compiled);
        $compiledContainer = new \PropertyAccessContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SingleLiteralClass' => new \Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass(\$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ClassWithLiteralArguments')->foo),", $compiled);
        $instance = $compiledContainer->get(SingleLiteralClass::class);
        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('a', $instance->value);
    }

    #[Test]
    public function taggedServiceIterator()
    {
        $builder = new ContainerBuilder();
        $builder->register(TaggedA::class)->tag(MyTagInterface::class);
        $builder->register(TaggedB::class)->tag(MyTagInterface::class);
        $builder->register(TagContainer::class)->arg(new TaggedServiceIterator(MyTagInterface::class));

        $container = $builder->build();

        $compiled = $container->compile(new PhpClassContainerCompiler('TaggedServiceIteratorContainerTest'));

        eval($compiled);
        $compiledContainer = new \TaggedServiceIteratorContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\Tagged\\\TagContainer' => new \Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TagContainer([\$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\Tagged\\\TaggedA'), \$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\Tagged\\\TaggedB'), ]),", $compiled);
        $instance = $compiledContainer->get(TagContainer::class);
        $this->assertInstanceOf(TagContainer::class, $instance);
        $this->assertInstanceOf(TaggedA::class, $instance->tagged[0]);
        $this->assertInstanceOf(TaggedB::class, $instance->tagged[1]);
    }

    #[Test]
    public function withStaticFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(StaticFactory::create(...))
            ->arg('test')
        ;

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('StaticFactoryContainerTest'));

        eval($compiled);
        $compiledContainer = new \StaticFactoryContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SingleLiteralClass' => \Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory::create('test'),", $compiled);
        $instance = $compiledContainer->get(SingleLiteralClass::class);
        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('TEST', $instance->value);
    }

    #[Test]
    public function withMethodFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(new MethodServiceFactory(new Reference(InstanceFactory::class), 'create'))
            ->arg('value')
        ;
        $builder->register(InstanceFactory::class)->arg('Suffix');

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('MethodFactoryContainerTest'));

        eval($compiled);
        $compiledContainer = new \MethodFactoryContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SingleLiteralClass' => \$this->get('Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\InstanceFactory')->create('value'),", $compiled);
        $instance = $compiledContainer->get(SingleLiteralClass::class);
        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('valueSuffix', $instance->value);
    }

    #[Test]
    public function inlineObjectArguments()
    {
        $builder = new ContainerBuilder();
        $builder->register(ContainerClass::class)
            ->arg(new SimpleClass())
            ->arg(new ClassWithLiteralArguments('foo', 42))
        ;

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('InlineObjectArgumentsContainerTest'));

        eval($compiled);
        $compiledContainer = new \InlineObjectArgumentsContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\ContainerClass' => new \Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass(new \Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass(), new \Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments('foo', 42)),", $compiled);
        $instance = $compiledContainer->get(ContainerClass::class);
        $this->assertInstanceOf(ContainerClass::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->simpleClass);
        $this->assertInstanceOf(ClassWithLiteralArguments::class, $instance->classWithLiteralArguments);
        $this->assertSame('foo', $instance->classWithLiteralArguments->foo);
        $this->assertSame(42, $instance->classWithLiteralArguments->bar);
        $this->assertFalse($compiledContainer->has(SimpleClass::class));
        $this->assertFalse($compiledContainer->has(ClassWithLiteralArguments::class));
    }

    #[Test]
    public function withInlineMethodFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(new MethodServiceFactory(new Literal(new InstanceFactory('Suffix')), 'create'))
            ->arg('value')
        ;

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('InlineMethodFactoryContainerTest'));

        eval($compiled);
        $compiledContainer = new \InlineMethodFactoryContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SingleLiteralClass' => new \Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory('Suffix')->create('value'),", $compiled);
        $instance = $compiledContainer->get(SingleLiteralClass::class);
        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('valueSuffix', $instance->value);
    }

    #[Test]
    public function withStringFunction()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(__NAMESPACE__ . '\global_factory_function')
            ->arg('value')
        ;

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('GlobalFunctionFactoryContainerTest'));

        eval($compiled);
        $compiledContainer = new \GlobalFunctionFactoryContainerTest();

        $this->assertStringContainsString("'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SingleLiteralClass' => \Arakne\Tests\Spinneret\Container\Compiler\global_factory_function('value'),", $compiled);
        $instance = $compiledContainer->get(SingleLiteralClass::class);
        $this->assertInstanceOf(SingleLiteralClass::class, $instance);
        $this->assertSame('value', $instance->value);
    }

    #[Test]
    public function withAliases()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->alias('a', SimpleClass::class);
        $builder->alias('b', 'a');

        $container = $builder->build();
        $compiled = $container->compile(new PhpClassContainerCompiler('AliasContainerTest'));

        eval($compiled);
        $compiledContainer = new \AliasContainerTest();

        $this->assertStringContainsString("'a' => 'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass',", $compiled);
        $this->assertStringContainsString("'b' => 'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\SimpleClass',", $compiled);
        $this->assertTrue($compiledContainer->has(SimpleClass::class));
        $this->assertTrue($compiledContainer->has('a'));
        $this->assertTrue($compiledContainer->has('b'));
        $this->assertInstanceOf(SimpleClass::class, $compiledContainer->get(SimpleClass::class));
        $this->assertSame($compiledContainer->get(SimpleClass::class), $compiledContainer->get('a'));
        $this->assertSame($compiledContainer->get(SimpleClass::class), $compiledContainer->get('b'));
    }

    #[Test]
    public function cannotCompileClosureFactory()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Failed to compile service "'.SingleLiteralClass::class.'": Cannot compile a function that is not a string.');

        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(static fn() => new SingleLiteralClass('test'))
        ;
        $builder->build()->compile();
    }
}

function global_factory_function(string $v): SingleLiteralClass
{
    return new SingleLiteralClass($v);
}
