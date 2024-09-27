<?php

namespace Arakne\Spinneret\Logger;

use Stringable;

use function date;
use function is_scalar;
use function json_encode;
use function str_replace;
use function strtoupper;
use function var_export;

final readonly class Formatter
{
    /**
     * Format log message
     *
     * Allow to use placeholders in the message, replaced by the context values,
     * using format `{{ key }}` in the message.
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

        foreach ($context as $key => $value) {
            if (!is_scalar($value) && !$value instanceof Stringable) {
                $value = var_export($value, true);
            }

            $message = str_replace('{{ ' . $key . ' }}', (string) $value, $message);
        }

        $contextString = $context ? ' ' . json_encode($context) : '';

        return $formatted . $message . $contextString;
    }
}
