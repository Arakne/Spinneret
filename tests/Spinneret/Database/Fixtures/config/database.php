<?php

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;

return new DatabaseConfig(
    connections: [
        new ConnectionConfig('test', 'sqlite::memory:'),
        new ConnectionConfig('other', 'sqlite::memory:'),
    ],
);
