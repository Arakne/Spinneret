<?php

namespace Arakne\Tests\Spinneret\Runner\Backend\Httpd;

use Arakne\Spinneret\Error\ErrorHandler;
use Arakne\Spinneret\Runner\Backend\Httpd\HttpdBackend;
use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpdBackendTest extends TestCase
{
    #[Test, RunInSeparateProcess]
    public function functionalSimple()
    {
        $app = new TestApplication(true, 'test');
        $psr17Factory = new Psr17Factory();
        ErrorHandler::restore();
        $backend = new HttpdBackend($app, new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory));

        $_SERVER['REQUEST_URI'] = '/hello';
        $_GET['name'] = 'John';

        ob_start();
        $backend->run();
        $output = ob_get_clean();

        $this->assertSame(
            <<<'HTML'
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
            HTML,
            $output
        );
    }

    #[Test, RunInSeparateProcess]
    public function functionalBigContent()
    {
        $app = new TestApplication(true, 'test');
        $psr17Factory = new Psr17Factory();
        ErrorHandler::restore();
        $backend = new HttpdBackend($app, new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory));

        $_SERVER['REQUEST_URI'] = '/download';
        $_GET['seed'] = '125';
        $_GET['size'] = 2 * 1024 * 1024;
        $_GET['filename'] = 'big.txt';

        ob_start();
        $backend->run();
        $output = ob_get_clean();

        $this->assertSame(2 * 1024 * 1024, strlen($output));
        if (function_exists('xdebug_get_headers')) {
            $this->assertSame([
                'Content-Disposition: attachment; filename="big.txt"',
                'Content-Type: application/octet-stream',
            ], xdebug_get_headers());
        }
    }
}
