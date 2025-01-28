<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\DispatcherForwarder;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewEngineInterface;
use Arakne\Tests\Spinneret\Application\Fixtures\Forward\TestForwardModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRequest;
use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;
use Arakne\Tests\Web\TestModule;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function ob_start;

class DispatcherForwarderTest extends TestCase
{
    private TestApplication $app;
    private DispatcherForwarder $forwarder;

    protected function setUp(): void
    {
        $this->app = new class(isDev: true, env: 'test') extends TestApplication {
            public function applicationModules(): array
            {
                return [...parent::applicationModules(), new TestModule(), new TestForwardModule()];
            }
        };
        $this->forwarder = $this->app->get(DispatcherForwarder::class);
    }

    #[Test]
    public function render()
    {
        $content = $this->forwarder->render(new HelloRequest(), new View(
            $this->app->get(ViewEngineInterface::class),
            new stdClass(),
            new ServerRequest('GET', '/'),
        ));

        $this->assertEquals(
            <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Hello</title>
                </head>
                <body>
                    <h1>Hello, World!</h1>
                </body>
            </html>
            HTML,
            $content
        );
    }

    #[Test]
    public function display()
    {
        ob_start();
        $this->forwarder->display(new HelloRequest(), new View(
            $this->app->get(ViewEngineInterface::class),
            new stdClass(),
            new ServerRequest('GET', '/'),
        ));
        $content = ob_get_clean();

        $this->assertEquals(
            <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Hello</title>
                </head>
                <body>
                    <h1>Hello, World!</h1>
                </body>
            </html>
            HTML,
            $content
        );
    }

    #[Test]
    public function functional()
    {
        $response = $this->app->handle(new ServerRequest('GET', '/forward'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            <<<'HTML'
                    <!DOCTYPE html>
                    <html lang="en">
                        <head>
                            <title>Test Forward</title>
                        </head>
                        <body>
                            <h1>Test Forward</h1>
                            <pre>{"message":"My configured message","computed":1655275095}</pre>
                        </body>
                    </html>
                    
            HTML,
            (string) $response->getBody()
        );
    }
}
