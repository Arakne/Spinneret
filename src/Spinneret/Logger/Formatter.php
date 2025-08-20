<?php

namespace Arakne\Spinneret\Logger;

use Stringable;
use UnitEnum;

use function array_is_list;
use function array_map;
use function date;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function json_encode;
use function str_contains;
use function str_replace;
use function strtoupper;
use function var_export;

final readonly class Formatter
{
    /**
     * Format log message
     *
     * Allow to use placeholders in the message, replaced by the context values,
     * using format `{key}` in the message.
     *
     * @param mixed $level The error level. Should be stringable
     * @param Stringable|string $message
     * @param array<array-key, mixed> $context
     * @param int|null $timestamp The current timestamp. If null, the current time will be used
     *
     * @return string
     */
    public static function message($level, Stringable|string $message, array $context = [], ?int $timestamp = null): string
    {
        $formatted = date('Y-m-d H:i:s', $timestamp) . ' ' . strtoupper((string) $level) . ' ';
        $message = (string) $message;

        if ($context && str_contains($message, '{')) {
            /**
             * @var array-key $key
             * @var mixed $value
             */
            foreach ($context as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $value = self::value($value);
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }

        $contextString = $context ? ' ' . (string) json_encode($context) : '';

        return $formatted . $message . $contextString;
    }

    /**
     * Convert a value to a string representation
     *
     * @param mixed $value
     * @return string
     */
    public static function value(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value) || $value instanceof Stringable || $value === null) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value) && array_is_list($value)) {
            return '[' . implode(', ', array_map(self::value(...), $value)) . ']';
        }

        return var_export($value, true);
    }
}
