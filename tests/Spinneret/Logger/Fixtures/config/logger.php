<?php

use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\LogChannel;
use Arakne\Spinneret\Logger\LoggerConfiguration;
use Psr\Log\LogLevel;

return (new LoggerConfiguration(
    new LogChannel(
        file: '%app.log_dir%/app.log',
        minLevel: LogLevel::NOTICE,
    ),
    new LogChannel(
        file: '%app.log_dir%/debug.log',
        bufferSize: 10,
        maxLevel: LogLevel::INFO,
        contextKeys: ['debug'],
    ),
    new LogChannel(
        service: ArrayLogger::class,
    )
))
    ->with(new LogChannel(
        file: '%app.log_dir%/test.log',
        filter: fn (mixed $level, string|Stringable $message, array $context): bool => \str_contains((string) $message, 'test') && \count($context) > 1,
    ))
;
