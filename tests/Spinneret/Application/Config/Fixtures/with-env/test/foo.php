<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\FooConfig;

return fn(FooConfig $config) => new FooConfig(
    foo: strtoupper($config->foo),
);
