<?php

namespace Arakne\Spinneret\Logger\Driver;

use Arakne\Spinneret\Logger\Formatter;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function mkdir;
use function strlen;
use function time;

/**
 * Logger implementation for writing logs to a file
 * The implementation uses a buffer to reduce the number of write operations
 */
final class FileLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var resource|null
     */
    private mixed $stream = null;
    private string $buffer = '';
    private int $lastFlush;

    public function __construct(
        /**
         * The log file name
         *
         * If the directory does not exist, it will be created.
         * All logs will be appended to this file
         */
        private readonly string $filename,

        /**
         * The maximum buffer size before flushing the logs
         * It's advisable to set a value slightly lower than the filesystem block size
         * to ensure atomic writes
         */
        private readonly int $bufferSize = 2048,
    ) {
        $this->lastFlush = time();
    }

    #[Override]
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->buffer .= Formatter::message($level, $message, $context) . PHP_EOL;

        if (
            strlen($this->buffer) >= $this->bufferSize
            || time() - $this->lastFlush >= 2
        ) {
            $this->flush();
        }
    }

    /**
     * Write the buffer to the file
     * and clear the buffer
     */
    public function flush(): void
    {
        $stream = $this->open();

        if ($stream === null) {
            return;
        }

        $buffer = $this->buffer;
        $this->buffer = '';

        @fwrite($stream, $buffer);
        $this->lastFlush = time();
    }

    public function __destruct()
    {
        $this->flush();

        if ($this->stream !== null) {
            @fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * Try to open the file stream
     * This method is fail-safe and will return null if the file cannot be opened
     *
     * @return resource|null
     */
    private function open(): mixed
    {
        if ($this->stream !== null) {
            return $this->stream;
        }

        $dir = dirname($this->filename);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0o777, true)) {
                return null;
            }
        }

        if (($resource = @fopen($this->filename, 'a')) === false) {
            return null;
        }

        return $this->stream = $resource;
    }
}
