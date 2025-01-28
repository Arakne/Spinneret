<?php

use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanConfig;
use Arakne\Spinneret\Runner\RunnerConfig;

return new RunnerConfig(
    workerman: new WorkermanConfig(
        enable: true,
        port: 12001,
    ),
);
