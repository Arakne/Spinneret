<?php

use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\LogChannel;
use Arakne\Spinneret\Logger\LoggerConfiguration;

return new LoggerConfiguration(
    new LogChannel(
        service: ArrayLogger::class,
    )
);
