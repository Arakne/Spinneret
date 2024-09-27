<?php

namespace Arakne\Spinneret\Logger;

use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

/**
 * Logger implementation for dispatching logs to multiple loggers depending on a filter
 */
final readonly class LoggerDispatcher implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var list<LoggerFilter>
     */
    private array $loggers;

    public function __construct(LoggerFilter ...$loggers)
    {
        $this->loggers = $loggers;
    }

    #[Override]
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $intLevel = LoggerFilter::levelToInt($level);

        foreach ($this->loggers as $logger) {
            if ($logger->match($intLevel, $message, $context)) {
                $logger->logger->log($level, $message, $context);
            }
        }
    }
}
