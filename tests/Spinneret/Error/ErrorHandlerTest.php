<?php

namespace Arakne\Tests\Spinneret\Error;

use Arakne\Spinneret\Error\ErrorConfiguration;
use Arakne\Spinneret\Error\ErrorHandler;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    #[Test]
    public function register()
    {
        $handler = new ErrorHandler(new ErrorConfiguration(), null);
        $handler->register();

        /** @var \Closure $handler */
        $errorHandler = set_error_handler(null);
        /** @var \Closure $handler */
        $exceptionHandler = set_exception_handler(null);

        $this->assertInstanceOf(\Closure::class, $errorHandler);
        $this->assertSame('handleError', (new \ReflectionFunction($errorHandler))->getName());
        $this->assertSame($handler, (new \ReflectionFunction($errorHandler))->getClosureThis());

        $this->assertInstanceOf(\Closure::class, $exceptionHandler);
        $this->assertSame('handleException', (new \ReflectionFunction($exceptionHandler))->getName());
        $this->assertSame($handler, (new \ReflectionFunction($exceptionHandler))->getClosureThis());
    }

    #[Test]
    public function restore()
    {
        set_error_handler($currentErrorHandler = set_error_handler(null));
        set_exception_handler($currentExceptionHandler = set_exception_handler(null));

        $handler = new ErrorHandler(new ErrorConfiguration(), null);
        $handler->register();

        ErrorHandler::restore();

        $this->assertSame($currentErrorHandler, set_error_handler(null));
        $this->assertSame($currentExceptionHandler, set_exception_handler(null));
    }

    #[Test]
    public function handleError()
    {
        $previous = set_error_handler(function () use (&$args) {
            $args = func_get_args();
            return true;
        });

        $handler = new ErrorHandler(
            new ErrorConfiguration(
                errorReportingLevel: E_ALL & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_DEPRECATED,
                convertErrorsToExceptions: E_ALL & ~E_WARNING & ~E_USER_WARNING,
            ),
            null
        );
        $handler->register();

        $errorHandler = set_error_handler(null);

        $this->assertTrue($errorHandler(E_WARNING, 'Notice', 'file', 1));
        $this->assertSame([E_WARNING, 'Notice', 'file', 1], $args);

        $args = null;
        $this->assertFalse($errorHandler(E_NOTICE, 'Notice', 'file', 1));
        $this->assertNull($args);

        try {
            $errorHandler(E_ERROR, 'Notice', 'file', 1);
            $this->fail('An exception should have been thrown');
        } catch (\ErrorException $e) {
            $this->assertSame(E_ERROR, $e->getSeverity());
            $this->assertSame('Notice', $e->getMessage());
        }
        $this->assertNull($args);

        ErrorHandler::restore();
        ErrorHandler::restore();
        set_error_handler($previous);
    }

    #[Test]
    public function handleException()
    {
        $previous = set_exception_handler(function () use (&$args) {
            $args = func_get_args();
        });

        $handler = new ErrorHandler(
            new ErrorConfiguration(),
            $logger = new ArrayLogger(),
        );
        $handler->register();

        $exceptionHandler = set_exception_handler(null);

        $exception = new \Exception('Exception');

        try {
            $exceptionHandler($exception);
            $this->fail('The exception should have been rethrown');
        } catch (\Exception $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertSame(
            [
                'level' => 'critical',
                'message' => 'Uncaught exception {{ exception }}',
                'context' => ['exception' => $exception],
            ],
            $logger->logs[0]
        );

        $this->assertSame([$exception], $args);

        ErrorHandler::restore();
        set_exception_handler($previous);
    }
}
