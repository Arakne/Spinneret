<?php

return new \Arakne\Spinneret\Database\DatabaseConfig(
    useMigration: true,
    migrationConnection: 'test',
    connections: [
        new \Arakne\Spinneret\Database\ConnectionConfig(
            name: 'test',
            dsn: 'sqlite::memory:',
        )
    ]
);
