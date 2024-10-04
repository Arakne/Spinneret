<?php

return new \Arakne\Spinneret\Database\DatabaseConfig(connections: [
    new \Arakne\Spinneret\Database\ConnectionConfig(
        name: 'foo',
        dsn: 'mysql:host=localhost;dbname=foo',
        username: 'foo',
        password: 'bar',
        options: [
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
    )
]);
