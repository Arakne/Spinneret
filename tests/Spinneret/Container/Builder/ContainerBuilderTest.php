<?php

namespace Arakne\Tests\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Argument\DynamicArray;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\AutowireableFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\ComplexTag;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TagContainer;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\Tagged;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use SplPriorityQueue;

use function iterator_to_array;
use function var_dump;

class ContainerBuilderTest extends TestCase
{
    #[Test]
    public function withSimpleClass()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);

        $container = $builder->build();

        $this->assertTrue($container->has(SimpleClass::class));
        $this->assertInstanceOf(SimpleClass::class, $container->get(SimpleClass::class));
        $this->assertSame($container->get(SimpleClass::class), $container->get(SimpleClass::class));
    }

    #[Test]
    public function withLiteralArguments()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;

        $container = $builder->build();

        $this->assertTrue($container->has(ClassWithLiteralArguments::class));
        $this->assertInstanceOf(ClassWithLiteralArguments::class, $container->get(ClassWithLiteralArguments::class));
        $instance = $container->get(ClassWithLiteralArguments::class);
        $this->assertSame('test', $instance->foo);
        $this->assertSame(42, $instance->bar);
        $this->assertSame($instance, $container->get(ClassWithLiteralArguments::class));
    }

    #[Test]
    public function withReference()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;
        $builder->register(ContainerClass::class)
            ->arg(new Reference(SimpleClass::class))
            ->arg(new Reference(ClassWithLiteralArguments::class))
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(ContainerClass::class));
        $this->assertInstanceOf(ContainerClass::class, $container->get(ContainerClass::class));
        $instance = $container->get(ContainerClass::class);
        $this->assertSame($container->get(SimpleClass::class), $instance->simpleClass);
        $this->assertSame($container->get(ClassWithLiteralArguments::class), $instance->classWithLiteralArguments);
    }

    #[Test]
    public function withAliases()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->alias('a', SimpleClass::class);
        $builder->alias('b', 'a');

        $container = $builder->build();
        $this->assertTrue($container->has(SimpleClass::class));
        $this->assertTrue($container->has('a'));
        $this->assertTrue($container->has('b'));

        $this->assertSame($container->get(SimpleClass::class), $container->get('a'));
        $this->assertSame($container->get(SimpleClass::class), $container->get('b'));
    }

    #[Test]
    public function withAliasArgument()
    {

        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->alias('a', SimpleClass::class);
        $builder->alias('b', 'a');
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;
        $builder->register(ContainerClass::class)
            ->arg(new Reference('b'))
            ->arg(new Reference(ClassWithLiteralArguments::class))
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(ContainerClass::class));
        $this->assertInstanceOf(ContainerClass::class, $container->get(ContainerClass::class));
        $instance = $container->get(ContainerClass::class);
        $this->assertSame($container->get(SimpleClass::class), $instance->simpleClass);
        $this->assertSame($container->get(ClassWithLiteralArguments::class), $instance->classWithLiteralArguments);
    }

    #[Test]
    public function withReferenceAutowiring()
    {
        $builder = new ContainerBuilder();
        $builder->register(SimpleClass::class);
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;
        $builder->register(ContainerClass::class);

        $container = $builder->build();
        $this->assertTrue($container->has(ContainerClass::class));
        $this->assertInstanceOf(ContainerClass::class, $container->get(ContainerClass::class));
        $instance = $container->get(ContainerClass::class);
        $this->assertSame($container->get(SimpleClass::class), $instance->simpleClass);
        $this->assertSame($container->get(ClassWithLiteralArguments::class), $instance->classWithLiteralArguments);
    }

    #[Test]
    public function withReferenceAutowiringShouldAutoregisterMissingDependencies()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('test')
            ->arg(42)
        ;
        $builder->register(ContainerClass::class);

        $container = $builder->build();
        $this->assertTrue($container->has(ContainerClass::class));
        $this->assertInstanceOf(ContainerClass::class, $container->get(ContainerClass::class));
        $instance = $container->get(ContainerClass::class);
        $this->assertSame($container->get(SimpleClass::class), $instance->simpleClass);
        $this->assertSame($container->get(ClassWithLiteralArguments::class), $instance->classWithLiteralArguments);
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
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('TEST', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(StaticMethodServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
        $this->assertSame(StaticFactory::class, $container->services[SingleLiteralClass::class]->factory->class);
        $this->assertSame('create', $container->services[SingleLiteralClass::class]->factory->method);
    }

    #[Test]
    public function withStaticFactoryArraySyntax()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory([StaticFactory::class, 'create'])
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('TEST', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(StaticMethodServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
        $this->assertSame(StaticFactory::class, $container->services[SingleLiteralClass::class]->factory->class);
        $this->assertSame('create', $container->services[SingleLiteralClass::class]->factory->method);
    }

    #[Test]
    public function withInstanceFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(InstanceFactory::class)->arg('---');
        $builder->register(SingleLiteralClass::class)
            ->factory(new InstanceFactory('')->create(...))
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('test---', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(MethodServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
        $this->assertEquals(new Reference(InstanceFactory::class), $container->services[SingleLiteralClass::class]->factory->object);
        $this->assertSame('create', $container->services[SingleLiteralClass::class]->factory->method);
    }

    #[Test]
    public function withInstanceFactoryArraySyntax()
    {
        $builder = new ContainerBuilder();
        $builder->register(InstanceFactory::class)->arg('---');
        $builder->register(SingleLiteralClass::class)
            ->factory([new Reference(InstanceFactory::class), 'create'])
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('test---', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(MethodServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
        $this->assertEquals(new Reference(InstanceFactory::class), $container->services[SingleLiteralClass::class]->factory->object);
        $this->assertSame('create', $container->services[SingleLiteralClass::class]->factory->method);
    }

    #[Test]
    public function withClosureFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory(fn (string $value) => new SingleLiteralClass($value . '###'))
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('test###', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(FunctionServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
    }

    #[Test]
    public function withStringFactory()
    {
        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)
            ->factory('Arakne\Tests\Spinneret\Container\Builder\global_function_factory')
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertTrue($container->has(SingleLiteralClass::class));
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $instance = $container->get(SingleLiteralClass::class);
        $this->assertSame('test!!!', $instance->value);
        $this->assertSame($instance, $container->get(SingleLiteralClass::class));

        $this->assertInstanceOf(FunctionServiceFactory::class, $container->services[SingleLiteralClass::class]->factory);
    }

    #[Test]
    public function autowireFactoryService()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class)
            ->arg('aaaa')
            ->arg(42)
        ;
        $builder->register(SingleLiteralClass::class)
            ->factory([new Reference(AutowireableFactory::class), 'create'])
            ->arg('test')
        ;

        $container = $builder->build();
        $this->assertInstanceOf(SingleLiteralClass::class, $container->get(SingleLiteralClass::class));
        $this->assertSame('testaaaa', $container->get(SingleLiteralClass::class)->value);
    }

    #[Test]
    public function withTagAndProcessor()
    {
        $builder = new ContainerBuilder();
        $builder->register(TagContainer::class);
        $builder->register('a')->class(Tagged::class)->arg('a')->tag(new ComplexTag(1));
        $builder->register('b')->class(Tagged::class)->arg('b')->tag(new ComplexTag(5));
        $builder->register('c')->class(Tagged::class)->arg('c')->tag(new ComplexTag(2));
        $builder->processor(new class implements ContainerBuilderProcessorInterface {
            #[Override]
            public function process(ContainerBuilder $builder): void
            {
                $priority = new SplPriorityQueue();
                $container = $builder->services[TagContainer::class];

                foreach ($builder->findByTag(ComplexTag::class) as $service => $tags) {
                    foreach ($tags as $tag) {
                        $priority->insert(new Reference($service->id), $tag->priority);
                    }
                }

                $container->arguments[0] = iterator_to_array($priority, false);
            }
        });

        $container = $builder->build();
        $this->assertInstanceOf(TagContainer::class, $container->get(TagContainer::class));
        $this->assertCount(3, $container->get(TagContainer::class)->tagged);
        $this->assertSame('b', $container->get(TagContainer::class)->tagged[0]->value);
        $this->assertSame('c', $container->get(TagContainer::class)->tagged[1]->value);
        $this->assertSame('a', $container->get(TagContainer::class)->tagged[2]->value);

        $this->assertInstanceOf(DynamicArray::class, $container->services[TagContainer::class]->arguments[0]);
    }

    #[Test]
    public function invalidFactory()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Factory must be a callable or an array with two elements: [class, method].');

        $builder = new ContainerBuilder();
        $builder->register(SingleLiteralClass::class)->factory([]);
        $builder->build();
    }
}

function global_function_factory(string $value): SingleLiteralClass
{
    return new SingleLiteralClass($value . '!!!');
}
