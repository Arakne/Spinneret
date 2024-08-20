<?php

use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\TestConfig;

return function (?TestConfig $config) {
    return new TestConfig(
        message: $config->message,
        computed: crc32($config->message),
    );
};
