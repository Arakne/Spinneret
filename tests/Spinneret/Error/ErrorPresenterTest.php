<?php

namespace Arakne\Tests\Spinneret\Error;

use Arakne\Spinneret\Error\ErrorPresenter;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\Runner\RunnerStepEnum;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ErrorPresenterTest extends TestCase
{
    #[Test]
    public function handle()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $presenter = new ErrorPresenter($logger);

        $routedRequest = new RoutedRequest(
            $req = new ServerRequest('GET', '/'),
            new InternalServerError(
                RunnerStepEnum::Presenter,
                new \Exception('test'),
                $req,
            ),
            success: false,
        );

        $logger->expects($this->exactly(2))->method('error')->with(
            'Uncaught exception : ' . $routedRequest->routedRequest->error,
            (array) $routedRequest->routedRequest,
        );

        $this->assertSame($routedRequest->routedRequest, $presenter->handleError($routedRequest->routedRequest, $routedRequest));
        $this->assertSame($routedRequest->routedRequest, $presenter->handleSuccess($routedRequest->routedRequest, $routedRequest));
    }
}
