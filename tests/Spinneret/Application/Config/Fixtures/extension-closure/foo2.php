<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\FooConfig;

return static fn (FooConfig $config) => new FooConfig(strtoupper($config->foo));
