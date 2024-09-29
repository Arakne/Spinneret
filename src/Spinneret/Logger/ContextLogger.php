<?php

namespace Arakne\Spinneret\Logger;

use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logger decorator that adds context to the log message
 */
final readonly class ContextLogger implements LoggerInterface
{
    use LoggerTrait;

    private LoggerInterface $logger;
    private ?string $marker;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @param LoggerInterface $logger The logger to decorate
     * @param string|null $marker A marker to prepend to the log message
     * @param array<string, mixed> $context Additional context to add to the log message
     */
    public function __construct(LoggerInterface $logger, ?string $marker = null, array $context = [])
    {
        if (!$logger instanceof self) {
            $this->logger = $logger;
            $this->marker = $marker;
            $this->context = $context;
        } else {
            $this->logger = $logger->logger;
            $this->context = $context + $logger->context;

            if ($marker === null) {
                $this->marker = $logger->marker;
            } else {
                $this->marker = $logger->marker !== null ? $marker . ' ' .$logger->marker : $marker;
            }
        }
    }

    #[Override]
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if ($this->marker !== null) {
            $message = $this->marker . ' ' . $message;
        }

        $context += $this->context;

        $this->logger->log($level, $message, $context);
    }
}
