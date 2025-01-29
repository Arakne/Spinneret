<?php

namespace Arakne\Spinneret\Event;

use Override;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use Throwable;

use function get_debug_type;
use function json_encode;
use function var_dump;

final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, list<callable(object):void>>
     * @psalm-var class-string-map<E, list<callable(E):void>>
     */
    private array $loadedListeners = [];

    public function __construct(
        /**
         * Container used to resolve listener instances
         */
        private readonly ContainerInterface $container,

        /**
         * Map of event class to listener service ids
         *
         * @var array<class-string, list<string>>
         */
        private readonly array $listeners,

        private readonly ?LoggerInterface $logger = null
    ) {
    }

    #[Override]
    public function dispatch(object $event): void
    {
        $eventClass = $event::class;

        foreach ($this->listeners($eventClass) as $listener) {
            try {
                $this->logger?->debug(
                    'Dispatching event {event} to listener {listener}',
                    [
                        'event' => $eventClass,
                        'listener' => get_debug_type($listener),
                    ]
                );

                $listener($event);
            } catch (Throwable $e) {
                $this->logger?->error(
                    'An error occurred while handling event {event} on listener {listener} : {exception}',
                    [
                        'event' => $eventClass,
                        'exception' => $e,
                        'listener' => get_debug_type($listener),
                    ]
                );
            }
        }
    }

    /**
     * @param class-string<E> $eventClass
     * @return list<callable(E):void>
     *
     * @template E as object
     */
    private function listeners(string $eventClass): array
    {
        if (isset($this->loadedListeners[$eventClass])) {
            return $this->loadedListeners[$eventClass];
        }

        $listeners = [];

        foreach ($this->listeners[$eventClass] ?? [] as $listener) {
            /** @var callable(E):void */
            $listeners[] = $this->container->get($listener);
        }

        /** @var list<callable(E):void> $listeners */
        return $this->loadedListeners[$eventClass] = $listeners;
    }
}
