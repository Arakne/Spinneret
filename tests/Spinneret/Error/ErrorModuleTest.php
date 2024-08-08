<?php

namespace Arakne\Tests\Spinneret\Error;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Error\ErrorModule;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\Runner\RunnerStepEnum;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorModuleTest extends TestCase
{
    #[Test]
    public function functionalProd()
    {
        $app = new class(false) extends Application {
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
, $response->getBody()
);
    }

    #[Test]
    public function functionalDev()
    {
        $app = new class(true) extends Application {
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
        $this->assertStringContainsString('<p>test</p>', (string) $response->getBody());
        $this->assertStringContainsString('<p>During stage Presenter</p>', (string) $response->getBody());
        $this->assertStringContainsString('Arakne\Tests\Spinneret\Error\ErrorModuleTest->functionalDev()', (string) $response->getBody());
    }
}
