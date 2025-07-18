<?php

use Arakne\Spinneret\Application\Application;
use Arakne\Tests\Spinneret\Application\Config\Fixtures\FooConfig;

return static fn (Application $app) => new FooConfig(
    foo: $app->env . '-foo',
);
