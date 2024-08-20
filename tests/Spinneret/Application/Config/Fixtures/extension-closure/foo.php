<?php

use Arakne\Tests\Spinneret\Application\Config\Fixtures\FooConfig;

return static fn () => new FooConfig(
    foo: 'baz',
);
