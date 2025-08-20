<?php

namespace Arakne\Tests\Spinneret\Event;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Event\ContainerListenerProvider;
use Arakne\Tests\Spinneret\Event\Fixtures\Multi1EventListener;
use Arakne\Tests\Spinneret\Event\Fixtures\Multi2EventListener;
use Arakne\Tests\Spinneret\Event\Fixtures\SimpleEvent;
use Arakne\Tests\Spinneret\Event\Fixtures\SimpleEventListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContainerListenerProviderTest extends TestCase
{
    #[Test]
    public function returnsListenerFromContainer(): void
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $builder->register(SimpleEventListener::class);
        $container = $builder->build();
        $provider = new ContainerListenerProvider($container, [
            SimpleEvent::class => [SimpleEventListener::class],
        ]);
        $event = new SimpleEvent();
        $listeners = iterator_to_array($provider->getListenersForEvent($event));
        $this->assertSame([$container->get(SimpleEventListener::class)], $listeners);
    }

    #[Test]
    public function returnsSameListWhenAlreadyResolved(): void
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $builder->register(SimpleEventListener::class);
        $container = $builder->build();
        $provider = new ContainerListenerProvider($container, [
            SimpleEvent::class => [SimpleEventListener::class],
        ]);
        $this->assertSame($provider->getListenersForEvent(new SimpleEvent()), $provider->getListenersForEvent(new SimpleEvent()));
    }

    #[Test]
    public function returnsMultipleListeners(): void
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $builder->register(Multi1EventListener::class);
        $builder->register(Multi2EventListener::class);
        $container = $builder->build();
        $provider = new ContainerListenerProvider($container, [
            SimpleEvent::class => [Multi1EventListener::class, Multi2EventListener::class],
        ]);
        $event = new SimpleEvent();
        $listeners = $provider->getListenersForEvent($event);
        $this->assertSame([
            $container->get(Multi1EventListener::class),
            $container->get(Multi2EventListener::class),
        ], iterator_to_array($listeners));
    }

    #[Test]
    public function returnsEmptyForUnknownEvent(): void
    {
        $builder = new ContainerBuilder(registerAsPublic: true);
        $container = $builder->build();
        $provider = new ContainerListenerProvider($container, []);
        $event = new SimpleEvent();
        $listeners = iterator_to_array($provider->getListenersForEvent($event));
        $this->assertCount(0, $listeners);
    }
}
