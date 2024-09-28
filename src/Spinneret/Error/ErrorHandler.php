<?php

namespace Arakne\Spinneret\Error;

use ErrorException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

use function error_reporting;
use function func_get_args;

/**
 * Handle PHP errors and exceptions to log them and convert them to exceptions if needed.
 */
final class ErrorHandler
{
    /**
     * Store the error reporting level before registering the error handler.
     * This is used to detect if the error has been suppressed using the @-operator.
     *
     * In case of error suppression, the error reporting level will be temporarily changed to a lower level.
     * So to detect it, we simply have to check if the level is different from the one we stored.
     */
    private int $globalErrorReportingLevel = 0;

    /**
     * Store the previous error handler to call it if needed.
     *
     * @var (callable(int, string, string=, int=):bool)|null
     */
    private mixed $previousErrorHandler = null;

    /**
     * Store the previous exception handler to call it if needed.
     *
     * @var (callable(Throwable):void)|null
     */
    private mixed $previousExceptionHandler = null;

    /**
     * Does an instance has already been registered?
     */
    private static bool $registered = false;

    public function __construct(
        private readonly ErrorConfiguration $configuration,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    /**
     * Register the error handler and exception handler callbacks.
     * This method must be called only once, preferably at the beginning of the application.
     */
    public function register(): void
    {
        $this->globalErrorReportingLevel = error_reporting();
        $this->previousErrorHandler = set_error_handler($this->handleError(...));
        $this->previousExceptionHandler = set_exception_handler($this->handleException(...));
        self::$registered = true;
    }

    /**
     * Restore the previous error handler and exception handler callbacks.
     */
    public static function restore(): void
    {
        if (!self::$registered) {
            return;
        }

        restore_error_handler();
        restore_exception_handler();
        self::$registered = false;
    }

    private function handleError(int $severity, string $errstr, ?string $errFile = null, ?int $errLine = null): bool
    {
        // Error has been suppressed using the @-operator
        if ($this->globalErrorReportingLevel !== error_reporting()) {
            return false;
        }

        $this->logger?->log(
            $this->severityToLevel($severity),
            'Error {{ errno }} ' . $errstr . ' in {{ file }} on line {{ line }}',
            [
                'errno' => $severity,
                'file' => $errFile,
                'line' => $errLine,
            ]
        );

        if ($this->configuration->isIgnored($severity)) {
            return false;
        }

        if ($this->configuration->shouldConvertToException($severity)) {
            throw new ErrorException($errstr, 0, $severity, $errFile, $errLine);
        }

        if ($this->previousErrorHandler !== null) {
            /** @psalm-suppress MixedArgument */
            return ($this->previousErrorHandler)(...func_get_args());
        }

        return true;
    }

    private function handleException(Throwable $e): void
    {
        $this->logger?->critical(
            'Uncaught exception {{ exception }}',
            [
                'exception' => $e,
            ]
        );

        if ($this->previousExceptionHandler !== null) {
            ($this->previousExceptionHandler)($e);
        }

        throw $e;
    }

    /**
     * Convert PHP error severity to PSR-3 log level.
     *
     * @param int $severity
     * @return LogLevel::*
     */
    private function severityToLevel(int $severity): string
    {
        return match ($severity) {
            E_COMPILE_ERROR => LogLevel::EMERGENCY,
            E_CORE_ERROR => LogLevel::CRITICAL,
            E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => LogLevel::WARNING,
            E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE, E_STRICT => LogLevel::NOTICE,
            default => LogLevel::WARNING,
        };
    }
}
