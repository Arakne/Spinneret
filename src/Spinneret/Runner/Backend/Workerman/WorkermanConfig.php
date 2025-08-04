<?php

namespace Arakne\Spinneret\Runner\Backend\Workerman;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Router\RouterConfig;

final readonly class WorkermanConfig
{
    public function __construct(
        /**
         * Enable the workerman backend
         *
         * This value is resolved at compile time, so it should not be resolved using an environment variable
         */
        public bool $enable,

        /**
         * The name of the process
         */
        public string $name,

        /**
         * The host to bind the server to
         */
        public string $host,

        /**
         * The port to bind the server to
         *
         * @var positive-int
         */
        public int $port,

        /**
         * Does workmerman backend is accessible only via HTTPS ?
         *
         * This parameter is used to generate the request URI scheme.
         * It should be true if scheme defined on {@see RouterConfig::$baseUrl} is "https".
         */
        public bool $secure,

        /**
         * The number of processes to run
         *
         * @var positive-int
         */
        public int $processes,

        /**
         * Define the log file used by workerman
         *
         * Those logs are handled directly by workerman, so the logger module is not used,
         * and logs cannot be configured.
         */
        public string $logFile,

        /**
         * Define the PID file used by workerman to store the master process PID
         */
        public string $pidFile,
    ) {}

    /**
     * @param bool|null $enable
     * @param string|null $name
     * @param string|null $host
     * @param positive-int|null $port
     * @param bool|null $secure
     * @param positive-int|null $processes
     * @param string|null $logFile
     * @param string|null $pidFile
     *
     * @return self
     */
    public function with(
        ?bool $enable = null,
        ?string $name = null,
        ?string $host = null,
        ?int $port = null,
        ?bool $secure = null,
        ?int $processes = null,
        ?string $logFile = null,
        ?string $pidFile = null,
    ): self {
        return new self(
            enable: $enable ?? $this->enable,
            name: $name ?? $this->name,
            host: $host ?? $this->host,
            port: $port ?? $this->port,
            secure: $secure ?? $this->secure,
            processes: $processes ?? $this->processes,
            logFile: $logFile ?? $this->logFile,
            pidFile: $pidFile ?? $this->pidFile,
        );
    }

    public static function default(Application $app): self
    {
        return new self(
            enable: true,
            name: 'Spinneret Workerman',
            host: '0.0.0.0',
            port: 8501,
            secure: false,
            processes: 8,
            logFile: $app->logDir() . '/workerman.log',
            pidFile: $app->logDir() . '/workerman.pid',
        );
    }
}
