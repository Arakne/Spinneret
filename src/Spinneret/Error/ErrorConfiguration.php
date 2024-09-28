<?php

namespace Arakne\Spinneret\Error;

/**
 * Configure error handling.
 */
final readonly class ErrorConfiguration
{
    public function __construct(
        /**
         * Bit mask of error levels to handle by the error handler.
         * Should be a bit combination of the E_* constants.
         *
         * If an error is not in this mask, the default PHP error handler will be used.
         */
        public int $errorReportingLevel = E_ALL,

        /**
         * Bit mask of error levels to transform into exceptions.
         * Should be a bit combination of the E_* constants.
         *
         * If an error is not in this mask, the default PHP error handler will be used.
         */
        public int $convertErrorsToExceptions = E_ALL,
    ) {
    }

    /**
     * Check if the given error severity is ignored by the error handler.
     *
     * @param int $severity
     * @return bool True if the error is ignored, false otherwise.
     */
    public function isIgnored(int $severity): bool
    {
        return ($this->errorReportingLevel & $severity) !== $severity;
    }

    /**
     * Check if the given error severity should be converted to an exception.
     *
     * @param int $severity
     * @return bool True if the error should be converted, false otherwise.
     */
    public function shouldConvertToException(int $severity): bool
    {
        return ($this->convertErrorsToExceptions & $severity) === $severity;
    }
}
