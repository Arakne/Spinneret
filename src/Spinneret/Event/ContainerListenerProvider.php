<?php

namespace Arakne\Spinneret\Event;

use Override;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Loads event listeners from a PSR-11 container.
 * Map of event class to listener service ids is provided in the constructor.
 */
final class ContainerListenerProvider implements ListenerProviderInterface
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
    ) {}

    /**
     * @param E $event
     * @return iterable<callable(E):void>
     *
     * @template E of object
     */
    #[Override]
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;

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
