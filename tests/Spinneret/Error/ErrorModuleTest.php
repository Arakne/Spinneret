<?php

namespace Arakne\Tests\Spinneret\Error;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Error\ErrorModule;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\LoggerModule;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\Runner\RunnerStepEnum;
use Arakne\Tests\Spinneret\Error\Fixtures\PublicArrayLoggerModule;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorModuleTest extends TestCase
{
    #[Test]
    public function functionalProd()
    {
        $app = new class(false, env: 'test') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ErrorModule(),
                ];
            }
        };

        $response = $app->handleRoutedRequest(new RoutedRequest(
            $req = new ServerRequest('GET', '/'),
            new InternalServerError(
                RunnerStepEnum::Presenter,
                new \Exception('test'),
                $req,
            ),
            success: false,
        ));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Internal Server Error</title>
    </head>
    <body>
        <h1>Error 500</h1>
                    <p>Something went wrong</p>
            </body>
</html>
        
HTML
, (string) $response->getBody()
);
    }

    #[Test]
    public function functionalDev()
    {
        $app = new class(true, env: 'test') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ErrorModule(),
                ];
            }
        };

        $response = $app->handleRoutedRequest(new RoutedRequest(
            $req = new ServerRequest('GET', '/'),
            new InternalServerError(
                RunnerStepEnum::Presenter,
                new \Exception('test'),
                $req,
                new HelloRequest(),
            ),
            success: false,
        ));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('<p>test</p>', (string) $response->getBody());
        $this->assertStringContainsString('<p>During stage Presenter</p>', (string) $response->getBody());
        $this->assertStringContainsString('Arakne\Tests\Spinneret\Error\ErrorModuleTest->functionalDev()', (string) $response->getBody());
        $this->assertStringContainsString('Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest', (string) $response->getBody());
    }

    #[Test]
    public function functionalShouldLogErrors()
    {
        $app = new class(true, env: 'test') extends Application {
            protected function applicationModules(): array
            {
                return [
                    new ErrorModule(),
                    new LoggerModule(),
                    new PublicArrayLoggerModule(),
                ];
            }

            public function configDir(): string
            {
                return __DIR__ . '/Fixtures/config';
            }
        };

        try {
            trigger_error('My warning error', E_USER_WARNING);
            $this->fail('Should have thrown an exception');
        } catch (\ErrorException $e) {
            $this->assertSame('My warning error', $e->getMessage());
            $this->assertSame(E_USER_WARNING, $e->getSeverity());
        }

        $logger = $app->get(ArrayLogger::class);

        $this->assertSame([
            [
                'level' => 'warning',
                'message' => 'Error {errno} My warning error in {file} on line {line}',
                'context' => [
                    'errno' => E_USER_WARNING,
                    'file' => __FILE__,
                    'line' => 111,
                ],
            ],
        ], $logger->logs);
    }
}
