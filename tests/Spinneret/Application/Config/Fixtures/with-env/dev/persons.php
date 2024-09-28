<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonsConfig;

return fn (PersonsConfig $config) => $config->withPerson(
    new PersonConfig(
        firstName: '!!!',
        lastName: '!!!',
    ),
);
