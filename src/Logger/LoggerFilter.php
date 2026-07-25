<?php

namespace Arakne\Spinneret\Logger;

use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

use function is_int;

/**
 * Structure for store the logger with its filter
 *
 * @see LoggerDispatcher which uses this structure to select the loggers to dispatch
 */
final readonly class LoggerFilter
{
    public function __construct(
        public LoggerInterface $logger,

        /**
         * Minimum level to log (inclusive)
         * If the log level is greater than or equal to this level, the log will be dispatched
         */
        public ?int $levelMin = null,

        /**
         * Maximum level to log (inclusive)
         * If the log level is less than or equal to this level, the log will be dispatched
         */
        public ?int $levelMax = null,

        /**
         * Filter by context keys
         * If all given keys are present in the context, the log will be dispatched
         *
         * @var list<string>
         */
        public array $contextKeys = [],

        /**
         * Custom filter callback
         *
         * Takes as parameters:
         * - The log level (the original parameter of log() method)
         * - The message as string
         * - The context as array
         *
         * Returns true if the log should be dispatched
         *
         * @var (Closure(mixed, Stringable|string, array<array-key, mixed>):bool)|null
         */
        public ?Closure $filter = null,
    ) {}

    /**
     * Check if the log should be dispatched
     *
     * @param int $level
     * @param string $message
     * @param array<array-key, mixed> $context
     *
     * @return bool
     */
    public function match(int $level, string $message, array $context): bool
    {
        if ($this->levelMin !== null && $level < $this->levelMin) {
            return false;
        }

        if ($this->levelMax !== null && $level > $this->levelMax) {
            return false;
        }

        foreach ($this->contextKeys as $key) {
            if (!isset($context[$key])) {
                return false;
            }
        }

        if ($this->filter !== null) {
            return ($this->filter)($level, $message, $context);
        }

        return true;
    }

    /**
     * Convert a log level to an integer
     * The returned value is higher for more important levels
     *
     * @param mixed $level
     * @return int|null
     */
    public static function levelToInt(mixed $level): ?int
    {
        if (is_int($level) || $level === null) {
            return $level;
        }

        // @phpstan-ignore cast.string (levels are generally int|string, but psr/log doesn't enforce it. So we consider that the level is safe to cast to string)
        return match ((string) $level) {
            LogLevel::DEBUG     => 0,
            LogLevel::INFO      => 1,
            LogLevel::NOTICE    => 2,
            LogLevel::WARNING   => 3,
            LogLevel::ERROR     => 4,
            LogLevel::CRITICAL  => 5,
            LogLevel::ALERT     => 6,
            LogLevel::EMERGENCY => 7,
            default             => 1,
        };
    }
}
