<?php

namespace Arakne\Spinneret\Bus;

/**
 * Base type for command bus dispatcher.
 * Can be used as implementation of CQRS pattern.
 *
 * Unlike event dispatcher, the every dispatched message must have exactly one handler.
 * If no handler is found, the dispatcher must throw an exception.
 */
interface BusDispatcherInterface
{
    /**
     * Dispatches the message to the handler.
     *
     * The command may be processed synchronously or asynchronously.
     * Domain exceptions should not be thrown here. This method should fail only on misconfiguration.
     *
     * @param M $message The command to dispatch
     *
     * @see BusDispatcherInterface::process() for perform synchronous processing
     * @template M as object
     */
    public function dispatch(object $message): void;

    /**
     * Dispatches the message to the handler and processes the result.
     *
     * Unlike {@see BusDispatcherInterface::dispatch()}, the command must be processed synchronously.
     * The result returned by the handler is passed to the $process callback, and the result of the callback is returned.
     *
     * Unlike {@see BusDispatcherInterface::dispatch()}, this method will not catch exceptions thrown by the handler.
     *
     * Usage:
     * ```php
     * $result = $bus->process($command, fn ($result) => $result->getValue());
     * ```
     *
     * @param M $message The command to dispatch
     * @param callable(T):R $process The callback to process the result
     *
     * @return R The result of the processing
     *
     * @template M as object
     * @template T
     * @template R
     */
    public function process(object $message, callable $process): mixed;
}
