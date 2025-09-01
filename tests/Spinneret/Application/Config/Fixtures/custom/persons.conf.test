<?php

use Arakne\Spinneret\Application\Application;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonConfig;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\PersonsConfig;

return static fn (PersonsConfig $config, Application $app) => $config->withPerson(new PersonConfig(
    firstName: $app->env,
    lastName: $app->env,
));
