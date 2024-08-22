<?php

namespace Arakne\Spinneret\Bus;

interface BusDispatcherInterface
{
    public function dispatch(object $message): void;

    /**
     * @param object $message
     * @param callable(T):R $process
     *
     * @return R
     *
     * @template T
     * @template R
     */
    public function process(object $message, callable $process): mixed;
}
