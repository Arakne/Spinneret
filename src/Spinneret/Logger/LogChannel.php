<?php

namespace Arakne\Spinneret\Logger;

use Arakne\Spinneret\Logger\Driver\FileLogger;
use Closure;
use Psr\Log\LogLevel;

/**
 * Configure a log channel.
 *
 * A channel consist of a logger driver configuration and filters.
 * At least one of the file or service arguments must be set.
 */
final readonly class LogChannel
{
    public function __construct(
        /**
         * The file to write logs to.
         * The parameter "%app.log_dir%" can be used to refer to the application log directory.
         *
         * If set, {@see FileLogger} will be used as the logger driver.
         */
        public ?string $file = null,

        /**
         * Define the service to use as the logger driver.
         * The service must be present in the container, and implement {@see LoggerInterface}.
         */
        public ?string $service = null,

        /**
         * In case of file logger, the buffer size to use, in bytes.
         */
        public ?int $bufferSize = null,

        /**
         * The minimum log levels to accept, inclusive.
         * If null, no minimum level is set.
         *
         * @var LogLevel::*|null
         */
        public ?string $minLevel = null,

        /**
         * The maximum log levels to accept, inclusive.
         * If null, no maximum level is set.
         *
         * @var LogLevel::*|null
         */
        public ?string $maxLevel = null,

        /**
         * Log only messages with all the given context keys present.
         *
         * @var list<string>
         */
        public array $contextKeys = [],

        /**
         * Add a custom filter to the logger.
         *
         * Takes as parameters:
         * - The log level (the original parameter of log() method)
         * - The message as string
         * - The context as array
         *
         * Returns true if the log should be written.
         *
         * Unlike other parameters, which are resolved at compile time, this parameter is resolved at runtime.
         * So, you can use environment variables or other runtime values into the filter.
         *
         * @var (Closure(mixed, string|\Stringable, array):bool)|null
         */
        public ?Closure $filter = null,
    ) {}
}
