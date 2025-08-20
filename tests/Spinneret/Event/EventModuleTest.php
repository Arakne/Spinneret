<?php

namespace Arakne\Tests\Spinneret\Event;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Event\Attribute\EventListener;
use Arakne\Spinneret\Event\ContainerListenerProvider;
use Arakne\Spinneret\Event\EventDispatcher;
use Arakne\Spinneret\Event\EventModule;
use Arakne\Tests\Spinneret\Event\Fixtures\MethodListeners;
use Arakne\Tests\Spinneret\Event\Fixtures\Multi1EventListener;
use Arakne\Tests\Spinneret\Event\Fixtures\Multi2EventListener;
use Arakne\Tests\Spinneret\Event\Fixtures\SimpleEvent;
use Arakne\Tests\Spinneret\Event\Fixtures\SimpleEventListener;
use Arakne\Tests\Spinneret\Event\Fixtures\StoppableEvent;
use Arakne\Tests\Spinneret\Event\Fixtures\StoppableEventListener;
use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use stdClass;

class EventModuleTest extends TestCase
{
    #[Test]
    public function registerServices()
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $container = $builder->build();

        $this->assertInstanceOf(ContainerListenerProvider::class, $container->get(ContainerListenerProvider::class));
        $this->assertInstanceOf(ContainerListenerProvider::class, $container->get(ListenerProviderInterface::class));
        $this->assertInstanceOf(EventDispatcher::class, $container->get(EventDispatcher::class));
        $this->assertInstanceOf(EventDispatcher::class, $container->get(EventDispatcherInterface::class));
    }

    #[Test]
    public function shouldRegisterClassListener()
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->register(SimpleEventListener::class);
        $builder->register(StoppableEventListener::class);
        $builder->register(Multi1EventListener::class);
        $builder->register(Multi2EventListener::class);

        $container = $builder->build();

        $provider = $container->get(ListenerProviderInterface::class);
        $this->assertSame([
            $container->get(SimpleEventListener::class),
            $container->get(Multi1EventListener::class),
            $container->get(Multi2EventListener::class),
        ], $provider->getListenersForEvent(new SimpleEvent()));
        $this->assertSame([
            $container->get(StoppableEventListener::class),
        ], $provider->getListenersForEvent(new StoppableEvent()));
        $this->assertSame([], $provider->getListenersForEvent(new stdClass()));
    }

    #[Test]
    public function shouldRegisterClassListenerExplicitTag()
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set($listener = new #[EventListener(SimpleEvent::class)] class {
            public function __invoke(object $event): void
            {
            }
        });

        $container = $builder->build();

        $provider = $container->get(ListenerProviderInterface::class);
        $this->assertSame([$listener], $provider->getListenersForEvent(new SimpleEvent()));
    }

    #[Test]
    public function shouldRaiseErrorIfInvokeMethodIsMissing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must have a public __invoke method to be used as an event listener.');

        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set(new #[EventListener] class {});
        $builder->build();
    }

    #[Test]
    public function shouldRaiseErrorIfTooManyParameters()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method __invoke must have exactly one parameter to be used as an event listener.');

        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set(new #[EventListener] class {
            public function __invoke(SimpleEvent $event, bool $foo): void
            {
            }
        });
        $builder->build();
    }

    #[Test]
    public function shouldRaiseErrorIfTooFewParameters()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method __invoke must have exactly one parameter to be used as an event listener.');

        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set(new #[EventListener] class {
            public function __invoke(): void
            {
            }
        });
        $builder->build();
    }

    #[Test]
    public function shouldRaiseErrorIfCannotResolveEventType()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method __invoke must have a single parameter with a class type to be used as an event listener.');

        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set(new #[EventListener] class {
            public function __invoke(string $event): void
            {
            }
        });
        $builder->build();
    }

    #[Test]
    public function shouldRaiseErrorIfCTypeIsMissing()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method __invoke must have a single parameter with a class type to be used as an event listener.');

        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->set(new #[EventListener] class {
            public function __invoke($event): void
            {
            }
        });
        $builder->build();
    }

    #[Test]
    public function shouldRegisterMethodListeners()
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $module = new EventModule();
        $module->register($builder);

        $builder->register(MethodListeners::class);

        $container = $builder->build();

        $provider = $container->get(ListenerProviderInterface::class);

        $simpleEventListeners = $provider->getListenersForEvent(new SimpleEvent());
        $this->assertCount(3, $simpleEventListeners);
        $this->assertContainsOnly(Closure::class, $simpleEventListeners);
        $event = new SimpleEvent();
        foreach ($simpleEventListeners as $listener) {
            $listener($event);
        }
        $this->assertSame([
            MethodListeners::class . '::onSimpleEvent',
            MethodListeners::class . '::onMixedEvents',
            MethodListeners::class . '::onExplicitEventType',
        ], $event->listeners);

        $stoppableEventListeners = $provider->getListenersForEvent(new StoppableEvent());
        $this->assertCount(2, $stoppableEventListeners);
        $this->assertContainsOnly(Closure::class, $stoppableEventListeners);
        $event = new StoppableEvent();
        foreach ($stoppableEventListeners as $listener) {
            $listener($event);
        }
        $this->assertSame([
            MethodListeners::class . '::onStoppableEvent',
            MethodListeners::class . '::onMixedEvents',
        ], $event->listeners);

        $this->assertSame([], $provider->getListenersForEvent(new stdClass()));
    }

    #[Test]
    public function functional()
    {
        $builder = new ContainerBuilder();
        $module = new EventModule();
        $module->register($builder);

        $builder->import(__DIR__.'/Fixtures', 'Arakne\Tests\Spinneret\Event\Fixtures');
        $container = $builder->build();

        $dispatcher = $container->get(EventDispatcherInterface::class);
        $event = $dispatcher->dispatch(new SimpleEvent());

        $this->assertEqualsCanonicalizing([
            'Arakne\Tests\Spinneret\Event\Fixtures\SimpleEventListener::__invoke',
            'Arakne\Tests\Spinneret\Event\Fixtures\Multi1EventListener::__invoke',
            'Arakne\Tests\Spinneret\Event\Fixtures\Multi2EventListener::__invoke',
            'Arakne\Tests\Spinneret\Event\Fixtures\MethodListeners::onSimpleEvent',
            'Arakne\Tests\Spinneret\Event\Fixtures\MethodListeners::onMixedEvents',
            'Arakne\Tests\Spinneret\Event\Fixtures\MethodListeners::onExplicitEventType',
        ], $event->listeners);
    }
}
