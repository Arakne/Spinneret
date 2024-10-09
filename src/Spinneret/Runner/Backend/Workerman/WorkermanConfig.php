<?php

namespace Arakne\Spinneret\Runner\Backend\Workerman;

final readonly class WorkermanConfig
{
    public function __construct(
        /**
         * Enable the workerman backend
         *
         * This value is resolved at compile time, so it should not be resolved using an environment variable
         */
        public bool $enable = false,

        /**
         * The name of the process
         */
        public string $name = 'Spinneret Workerman',

        /**
         * The host to bind the server to
         */
        public string $host = '0.0.0.0',

        /**
         * The port to bind the server to
         *
         * @var positive-int
         */
        public int $port = 8501,

        /**
         * The number of processes to run
         *
         * @var positive-int
         */
        public int $processes = 8,

        /**
         * Define the log file used by workerman
         *
         * Those logs are handled directly by workerman, so the logger module is not used,
         * and logs cannot be configured.
         *
         * Use "%app.log_dir%" to refer to the log directory.
         */
        public string $logFile = '%app.log_dir%/workerman.log',
    ) {
    }
}
