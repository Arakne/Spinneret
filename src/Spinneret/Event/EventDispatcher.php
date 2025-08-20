<?php

namespace Arakne\Spinneret\Event;

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function get_debug_type;

/**
 * Base implementation of PSR-14 event dispatcher.
 */
final readonly class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider,
        private ?LoggerInterface $logger = null
    ) {}

    #[Override]
    public function dispatch(object $event): object
    {
        $eventClass = $event::class;

        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
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

            if ($event instanceof StoppableEventInterface) {
                break;
            }
        }

        return $event;
    }
}
