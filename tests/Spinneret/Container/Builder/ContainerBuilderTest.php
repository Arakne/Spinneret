<?php

namespace Arakne\Tests\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Argument\DynamicArray;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Exception\ServiceNotFoundException;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\Attribute\BarListener;
use Arakne\Tests\Spinneret\Container\Fixtures\Attribute\EventDispatcher;
use Arakne\Tests\Spinneret\Container\Fixtures\Attribute\EventListener;
use Arakne\Tests\Spinneret\Container\Fixtures\Attribute\FooListener;
use Arakne\Tests\Spinneret\Container\Fixtures\AutowireableFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\ClassWithLiteralArguments;
use Arakne\Tests\Spinneret\Container\Fixtures\ContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\Controller\BarController;
use Arakne\Tests\Spinneret\Container\Fixtures\Controller\ControllerInterface;
use Arakne\Tests\Spinneret\Container\Fixtures\Controller\ControllerTag;
use Arakne\Tests\Spinneret\Container\Fixtures\Controller\FooController;
use Arakne\Tests\Spinneret\Container\Fixtures\Controller\FrontController;
use Arakne\Tests\Spinneret\Container\Fixtures\InstanceFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\NullableContainerClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SimpleClass;
use Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass;
use Arakne\Tests\Spinneret\Container\Fixtures\StaticFactory;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\ComplexTag;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TagContainer;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\Tagged;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\DepConfig;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\MessageDispatcher;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\MessageHandlerTag;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoA;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoAHandler;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoB;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\Messages\DoBHandler;
use Arakne\Tests\Spinneret\Container\Fixtures\WithLoader\SimpleDep;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplPriorityQueue;

use function iterator_to_array;

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
    public function withLiteralAs2ndParameter()
    {
        $builder = new ContainerBuilder();
        $builder->register(ClassWithLiteralArguments::class, ['test', 42]);

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
    public function anonymousWithTagAndProcessor()
    {
        $builder = new ContainerBuilder();
        $builder->register(TagContainer::class);
        $builder->anonymous(Tagged::class, ['a'])->tag(new ComplexTag(1));
        $builder->anonymous(Tagged::class, ['b'])->tag(new ComplexTag(5));
        $builder->anonymous(Tagged::class, ['c'])->tag(new ComplexTag(2));
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

    #[Test]
    public function buildWithoutClassOrFactory()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Error building service "test": Service must have a class or a factory.');

        $builder = new ContainerBuilder();
        $builder->register('test');
        $builder->build();
    }

    #[Test]
    public function configureInstanceOf()
    {
        $builder = new ContainerBuilder();
        $builder->configureInstanceOf(ControllerInterface::class, function (ServiceBuilder $service) {
            $service->tag(new ControllerTag($service->class::route()));
        });
        $builder->processor(new class implements ContainerBuilderProcessorInterface {
            #[Override]
            public function process(ContainerBuilder $builder): void
            {
                $frontController = $builder->services[FrontController::class];
                $controllers = [];

                foreach ($builder->findByTag(ControllerTag::class) as $service => $tags) {
                    foreach ($tags as $tag) {
                        $controllers[$tag->route] = new Reference($service->id);
                    }
                }

                $frontController->arguments[0] = $controllers;
            }
        });

        $builder->register(FooController::class);
        $builder->register(BarController::class);
        $builder->register(FrontController::class)->arg([]);

        $container = $builder->build();
        $this->assertTrue($container->has(FrontController::class));
        $this->assertInstanceOf(FrontController::class, $container->get(FrontController::class));
        $instance = $container->get(FrontController::class);
        $this->assertCount(2, $instance->controllers);
        $this->assertSame($container->get(FooController::class), $instance->controllers['/foo']);
        $this->assertSame($container->get(BarController::class), $instance->controllers['/bar']);
    }

    #[Test]
    public function configureAttribute()
    {
        $builder = new ContainerBuilder();
        $builder->configureAttribute(EventListener::class, function (ServiceBuilder $service, ContainerBuilder $containerBuilder, EventListener $attribute) {
            $service->tag($attribute);
        });
        $builder->processor(new class  implements ContainerBuilderProcessorInterface {
            #[Override]
            public function process(ContainerBuilder $builder): void
            {
                $eventDispatcher = $builder->services[EventDispatcher::class];
                $listeners = [];

                foreach ($builder->findByTag(EventListener::class) as $service => $attributes) {
                    foreach ($attributes as $attribute) {
                        $listeners[$attribute->event][] = new Reference($service->id);
                    }
                }

                $eventDispatcher->arguments[0] = $listeners;
            }
        });

        $builder->register(EventDispatcher::class)->arg([]);
        $builder->register(FooListener::class);
        $builder->register(BarListener::class);

        $container = $builder->build();
        $this->assertSame([
            'foo' => [$container->get(FooListener::class)],
            'bar' => [$container->get(BarListener::class)],
        ], $container->get(EventDispatcher::class)->listeners);
    }

    #[Test]
    public function autowireNullableShouldIgnoreIfInvalid()
    {
        $builder = new ContainerBuilder();
        $builder->register(NullableContainerClass::class);

        $container = $builder->build();

        $this->assertTrue($container->has(NullableContainerClass::class));
        $this->assertInstanceOf(NullableContainerClass::class, $container->get(NullableContainerClass::class));
        $instance = $container->get(NullableContainerClass::class);
        $this->assertNull($instance->dep);
    }

    #[Test]
    public function referenceNotNullOnInvalidShouldThrowError()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service "Arakne\Tests\Spinneret\Container\Fixtures\SingleLiteralClass" not found.');

        $builder = new ContainerBuilder();
        $builder->register(NullableContainerClass::class)->arg(new Reference(SingleLiteralClass::class));

        $container = $builder->build();

        $container->get(NullableContainerClass::class);
    }

    #[Test]
    public function import()
    {
        $builder = new ContainerBuilder();
        $builder->import(__DIR__ . '/../Fixtures/WithLoader', 'Arakne\Tests\Spinneret\Container\Fixtures\WithLoader');

        $builder->processor(new class implements ContainerBuilderProcessorInterface {
            #[Override]
            public function process(ContainerBuilder $builder): void
            {
                $handlers = [];
                $dispatcher = $builder->services[MessageDispatcher::class];

                foreach ($builder->findByTag(MessageHandlerTag::class) as $service => $attributes) {
                    foreach ($attributes as $attribute) {
                        $handlers[$attribute->message] = new Reference($service->id);
                    }
                }

                $dispatcher->arguments[0] = $handlers;
            }
        });
        $builder->register(DepConfig::class, ['my-key']);

        $container = $builder->build();
        $this->assertTrue($container->has(DepConfig::class));
        $this->assertTrue($container->has(DoAHandler::class));
        $this->assertTrue($container->has(DoBHandler::class));
        $this->assertTrue($container->has(MessageDispatcher::class));
        $this->assertTrue($container->has(SimpleDep::class));

        $this->assertSame('my-key', $container->get(DepConfig::class)->key);
        $this->assertInstanceOf(DoAHandler::class, $container->get(DoAHandler::class));
        $this->assertInstanceOf(DoBHandler::class, $container->get(DoBHandler::class));
        $this->assertSame($container->get(SimpleDep::class), $container->get(DoBHandler::class)->dep);
        $this->assertSame($container->get(DepConfig::class), $container->get(SimpleDep::class)->config);
        $this->assertInstanceOf(MessageDispatcher::class, $container->get(MessageDispatcher::class));
        $this->assertInstanceOf(MessageDispatcher::class, $container->get('dispatcher'));
        $this->assertSame([
            DoA::class => $container->get(DoAHandler::class),
            DoB::class => $container->get(DoBHandler::class),
        ], $container->get(MessageDispatcher::class)->handlers);
    }
}

function global_function_factory(string $value): SingleLiteralClass
{
    return new SingleLiteralClass($value . '!!!');
}
