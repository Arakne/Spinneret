<?php

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanConfig;
use Arakne\Spinneret\Runner\RunnerConfig;

return static fn (Application $app) => new RunnerConfig(
    workerman: WorkermanConfig::default($app)->with(
        enable: true,
        host: '127.0.0.1',
        port: 5123,
        processes: 2,
    ),
);
