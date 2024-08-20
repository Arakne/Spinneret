<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonsConfig;

return static fn () => new PersonsConfig(
    new PersonConfig(
        firstName: 'John',
        lastName: 'Doe',
        age: 42,
    ),
);
