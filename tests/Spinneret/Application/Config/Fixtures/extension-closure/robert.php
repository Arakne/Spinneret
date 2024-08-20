<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonsConfig;

return static fn (PersonsConfig $config) => $config->withPerson(
    new PersonConfig(
        firstName: 'Robert',
        lastName: 'Smith',
    ),
);
