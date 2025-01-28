<?php

namespace Arakne\Spinneret\Event;

/**
 * Interface for subscribers to multiple events.
 *
 * If you want to subscribe to a single event, simply use a functor class (i.e. a class with a __invoke method),
 * and register it as a service tagged with "spinneret.event.listener".
 *
 * To register a subscriber, create a service implementing this interface and tag it with the interface name.
 *
 * Example:
 * ```php
 * class MySubscriber implements EventSubscriberInterface
 * {
 *     public function onUserUpdate(UserUpdated $event): void
 *     {
 *         // Handle the event
 *     }
 *
 *     public function onUserDelete(UserDeleted $event): void
 *     {
 *        // Handle the event
 *     }
 *
 *     public function onMixedEvent(object $event): void
 *     {
 *        // Handle the event
 *     }
 *
 *    public static function getListenerMethods(): array
 *    {
 *        return [
 *            'onUserUpdate', 'onUserDelete', // Event class can be resolved from the method signature
 *
 *            // Explicitly define the event class. Multiple events can be handled by the same method.
 *            FooEvent::class => 'onMixedEvent',
 *            BarEvent::class => 'onMixedEvent',
 *        ];
 *    }
 * }
 * ```
 */
interface EventSubscriberInterface
{
    /**
     * List methods used as event listeners.
     *
     * The key is the event class name, the value is the method name.
     * The key may be missed if the event class can be resolved from the method signature.
     *
     * All listeners methods must be public, with a single parameter with the event object.
     *
     * @return array<class-string|int, string>
     */
    public static function getListenerMethods(): array;
}
