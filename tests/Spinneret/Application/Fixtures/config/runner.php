<?php

use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanConfig;
use Arakne\Spinneret\Runner\RunnerConfig;

return new RunnerConfig(
    workerman: new WorkermanConfig(
        enable: true,
        host: '127.0.0.1',
        port: 5123,
    ),
);
