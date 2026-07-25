<?php

namespace Arakne\Spinneret\Logger\Driver;

use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Dummy logger implementation for testing purposes
 */
final class ArrayLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var list<array{level: mixed, message: string, context: array<array-key, mixed>}>
     */
    public array $logs = [];

    #[Override]
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
