<?php

namespace Arakne\Tests\Spinneret\Event;

use Arakne\Spinneret\Event\EventDispatcher;
use Arakne\Tests\Spinneret\Event\Fixtures\SimpleEvent;
use Arakne\Tests\Spinneret\Event\Fixtures\StoppableEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

class EventDispatcherTest extends TestCase
{
    #[Test]
    public function dispatchSimpleEvent(): void
    {
        $event = new SimpleEvent();
        $listener = function (SimpleEvent $e) { $e->handled = true; };
        $provider = new class([$listener]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $dispatcher = new EventDispatcher($provider);
        $result = $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
        $this->assertSame($event, $result);
    }

    #[Test]
    public function dispatchMultipleListeners(): void
    {
        $event = new SimpleEvent();
        $calls = [];
        $listener1 = function (SimpleEvent $e) use (&$calls) { $calls[] = 1; };
        $listener2 = function (SimpleEvent $e) use (&$calls) { $calls[] = 2; };
        $provider = new class([$listener1, $listener2]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);
        $this->assertSame([1, 2], $calls);
    }

    #[Test]
    public function dispatchStoppableEventStopsPropagation(): void
    {
        $event = new StoppableEvent();
        $listener1 = function (StoppableEvent $e) { $e->handled = true; $e->stopPropagation(); };
        $listener2 = function (StoppableEvent $e) { $e->handled = false; };
        $provider = new class([$listener1, $listener2]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
    }

    #[Test]
    public function dispatchWithExceptionInListener(): void
    {
        $event = new SimpleEvent();
        $listener1 = function (SimpleEvent $e) { throw new \RuntimeException('fail'); };
        $listener2 = function (SimpleEvent $e) { $e->handled = true; };
        $provider = new class([$listener1, $listener2]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
    }

    #[Test]
    public function dispatchLogsDebugForEachListener(): void
    {
        $event = new SimpleEvent();
        $listener1 = function (SimpleEvent $e) {};
        $listener2 = function (SimpleEvent $e) {};
        $provider = new class([$listener1, $listener2]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->equalTo('Dispatching event {event} to listener {listener}'),
                $this->arrayHasKey('event')
            );
        $dispatcher = new EventDispatcher($provider, $logger);
        $dispatcher->dispatch($event);
    }

    #[Test]
    public function dispatchLogsErrorOnException(): void
    {
        $event = new SimpleEvent();
        $listener1 = function (SimpleEvent $e) { throw new \RuntimeException('fail'); };
        $listener2 = function (SimpleEvent $e) {};
        $provider = new class([$listener1, $listener2]) implements ListenerProviderInterface {
            public function __construct(private array $listeners) {}
            public function getListenersForEvent(object $event): iterable { return $this->listeners; }
        };
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug');
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('An error occurred while handling event {event} on listener {listener} : {exception}'),
                $this->arrayHasKey('event')
            );
        $dispatcher = new EventDispatcher($provider, $logger);
        $dispatcher->dispatch($event);
    }
}
