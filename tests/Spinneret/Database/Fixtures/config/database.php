<?php

use Arakne\Spinneret\Database\ConnectionConfig;
use Arakne\Spinneret\Database\DatabaseConfig;

return new DatabaseConfig(
    new ConnectionConfig('test', 'sqlite::memory:'),
);
