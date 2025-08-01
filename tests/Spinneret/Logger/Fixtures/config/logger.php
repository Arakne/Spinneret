<?php

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\LogChannel;
use Arakne\Spinneret\Logger\LoggerConfiguration;
use Psr\Log\LogLevel;

return static fn (Application $app) => new LoggerConfiguration(
    new LogChannel(
        file: $app->logDir() . '/app.log',
        minLevel: LogLevel::NOTICE,
    ),
    new LogChannel(
        file: $app->logDir() . '/debug.log',
        bufferSize: 10,
        maxLevel: LogLevel::INFO,
        contextKeys: ['debug'],
    ),
    new LogChannel(
        service: ArrayLogger::class,
    )
)
    ->with(new LogChannel(
        file: $app->logDir() . '/test.log',
        filter: fn (mixed $level, string|Stringable $message, array $context): bool => \str_contains((string) $message, 'test') && \count($context) > 1,
    ))
;
