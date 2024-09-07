<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Util\Files;
use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\ConfigurableModule;
use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\TestConfig;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloPresenter;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloRequest;
use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\Configuration\File;

class FunctionalApplicationTest extends TestCase
{
    protected TestApplication $app;

    protected function setUp(): void
    {
        $this->app = $this->createApplication();
    }

    protected function createApplication(): TestApplication
    {
        return new TestApplication(false);
    }

    public static function tearDownAfterClass(): void
    {
        Files::rmdir(dirname(__DIR__, 3).'/var/cache');
    }

    #[Test]
    public function directories()
    {
        $this->assertEquals(dirname(__DIR__, 3), $this->app->projectDir());
        $this->assertEquals(dirname(__DIR__, 3).'/var/cache', $this->app->cacheDir());
    }

    #[Test]
    public function modules()
    {
        $this->assertSame($this->app->modules(), $this->app->modules());
        $this->assertContainsOnlyInstancesOf(ModuleInterface::class, $this->app->modules());
        $this->assertIsList($this->app->modules());
    }

    #[Test]
    public function containerMethods()
    {
        $this->assertTrue($this->app->has(Application::class));
        $this->assertTrue($this->app->has(HelloPresenter::class));
        $this->assertFalse($this->app->has(HelloRequest::class));

        $this->assertSame($this->app, $this->app->get(Application::class));
        $this->assertInstanceOf(HelloPresenter::class, $this->app->get(HelloPresenter::class));
    }


    #[Test]
    public function modulesShouldSetConfiguration()
    {
        foreach ($this->app->modules() as $module) {
            if ($module instanceof ConfigurableModule) {
                break;
            }
        }

        $this->assertNotNull($module);
        $this->assertEquals(new TestConfig(
            message: 'My configured message',
            computed: 1655275095,
        ), $module->configuration());
    }

    #[Test]
    public function config()
    {
        $this->assertSame($this->app->config(), $this->app->config());

        $this->assertEquals([
            TestConfig::class => new TestConfig(
                message: 'My configured message',
                computed: 1655275095,
            ),
        ], $this->app->config());
    }

    #[Test]
    public function handleRoutedRequest()
    {
        $response = $this->app->handleRoutedRequest(new RoutedRequest(new ServerRequest('GET', '/'), new HelloRequest()));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(<<<'HTML'
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
HTML
            , (string) $response->getBody());
    }

    #[Test]
    public function hello()
    {
        $request = new ServerRequest('GET', '/hello');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(<<<'HTML'
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
HTML
, (string) $response->getBody());
    }

    #[Test]
    public function hello_with_parameter()
    {
        $request = new ServerRequest('GET', '/hello?name=John');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Hello</title>
    </head>
    <body>
        <h1>Hello, John!</h1>
    </body>
</html>
HTML
, (string) $response->getBody());
    }

    #[Test]
    public function not_found()
    {
        $request = new ServerRequest('GET', '/not-found');
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Not found</title>
    </head>
    <body>
        <h1>Error 404</h1>
        <p>This page cannot be found</p>
    </body>
</html>

HTML
            , (string) $response->getBody());
    }

    #[Test]
    public function bad_method()
    {
        $request = new ServerRequest('POST', '/hello');
        $response = $this->app->handle($request);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('GET', $response->getHeaderLine('Allow'));
        $this->assertEquals(<<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Method not allowed</title>
    </head>
    <body>
        <h1>Error 405</h1>
        <p>Method not allowed. Allow GET.</p>
    </body>
</html>

HTML
            , (string) $response->getBody());
    }

    #[Test]
    public function exception()
    {
        $request = new ServerRequest('GET', '/error');
        $response = $this->app->handle($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('<title>Internal Server Error</title>', (string) $response->getBody());

        if ($this->app->isDev) {
            $this->assertStringContainsString('<p>Error</p>', (string) $response->getBody());
            $this->assertStringContainsString('During stage Presenter', (string) $response->getBody());
            $this->assertStringContainsString('Arakne\Tests\Spinneret\Application\Fixtures\Error\RaiseErrorRequest', (string) $response->getBody());
        }
    }

    #[Test]
    public function postRequestSuccess()
    {
        $request = new ServerRequest('POST', '/register');
        $request = $request->withParsedBody([
            'name' => 'John',
            'email' => 'john.doe@example.com',
            'password' => '$tr0ngP@$$w0rd',
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('{"success":{"user":{"name":"John","email":"john.doe@example.com","password":"$tr0ngP@$$w0rd"}}}', (string) $response->getBody());
    }

    #[Test]
    public function postRequestError()
    {
        $request = new ServerRequest('POST', '/register');
        $request = $request->withParsedBody([
            'name' => '',
            'email' => 'invalid',
            'password' => '123',
        ]);

        $response = $this->app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('{"error":{"errors":{"name":"This value is required","email":"This value is not a valid.","password":"The password is too weak"}}}', (string) $response->getBody());
    }

    #[Test]
    public function withConfiguration()
    {
        $request = new ServerRequest('GET', '/config');
        $response = $this->app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals('{"message":"My configured message","computed":1655275095}', (string) $response->getBody());
    }
}
