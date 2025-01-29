<?php

namespace Arakne\Spinneret\Database;

use SensitiveParameter;
use UnitEnum;

/**
 * Configure a connection to a database
 */
final readonly class ConnectionConfig
{
    public function __construct(
        /**
         * The name of the connection
         * Can be an enum item. In this case, the enum item name will be used as the connection name.
         */
        public UnitEnum|string $name,

        /**
         * The PDO DSN for the connection
         */
        public string $dsn, // @todo do not use PDO DSN directly

        /**
         * The username to connect to the database
         */
        public string $username = 'root',

        /**
         * The password to connect to the database
         */
        #[SensitiveParameter]
        public string $password = '',

        /**
         * PDO options
         *
         * @var array<int, mixed>
         */
        public array $options = [],

        /**
         * Whether to automatically reconnect to the database if the connection is lost
         */
        public bool $autoReconnect = true,
    ) {}
}
