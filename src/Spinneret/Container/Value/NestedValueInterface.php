<?php

namespace Arakne\Spinneret\Container\Value;

use Generator;

/**
 * A ValueInterface which can contains other ValueInterface instances.
 * This is used to represent nested values, such as arrays or complex expressions.
 */
interface NestedValueInterface extends ValueInterface
{
    /**
     * Iterate over the nested ValueInterface instances.
     *
     * It can also be used to replace inner values, by using {@see Generator::send()},
     * and getting the new instance using {@see Generator::getReturn()}.
     *
     * Usage:
     * ```php
     * $generator = $value->traverse();
     *
     * // Iterate over inner values
     * while ($generator->valid()) {
     *     $currentValue = $generator->current();
     *
     *     if ($this->shouldBeProcessed($currentValue)) {
     *         // Replace the current value with a processed value
     *         $generator->send($this->processValue($currentValue));
     *     } else {
     *         $generator->next();
     *     }
     * }
     *
     * // Get the new value after inner values have been processed
     * $value = $generator->getReturn();
     * ```
     *
     * @return Generator<int, ValueInterface, ValueInterface|null, static>
     */
    public function traverse(): Generator;
}
