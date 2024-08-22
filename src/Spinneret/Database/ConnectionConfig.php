<?php

namespace Arakne\Spinneret\Database;

use UnitEnum;

final readonly class ConnectionConfig
{
    public function __construct(
        public UnitEnum|string $name,
        public string $dsn, // @todo do not use PDO DSN directly
        public string $username = 'root',
        public string $password = '',
    ) {
    }
}
