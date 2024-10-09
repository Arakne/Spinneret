<?php

namespace Arakne\Tests\Spinneret\Runner\Backend\Workerman;

use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanBackend;
use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionProperty;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

use function var_dump;

class WorkermanBackendTest extends TestCase
{
    private TestApplication $app;
    private WorkermanBackend $backend;

    protected function setUp(): void
    {
        $this->app = new TestApplication(isDev: true, env: 'test');
        $this->backend = $this->app->get(WorkermanBackend::class);
    }

    #[Test, DoesNotPerformAssertions]
    public function checkJit()
    {
        $this->backend->checkJit();
    }

    #[Test]
    public function init()
    {
        $this->backend->init();

        /** @var Worker $worker */
        $worker = (new ReflectionProperty($this->backend, 'worker'))->getValue($this->backend);

        $this->assertSame(8, $worker->count);
        $this->assertSame('Spinneret Workerman', $worker->name);
        $this->assertSame('tcp', $worker->transport);

        $r = new ReflectionFunction($worker->onMessage);

        $this->assertSame(WorkermanBackend::class, $r->getClosureCalledClass()->getName());
        $this->assertSame('handle', $r->name);
    }

    #[Test]
    public function handle()
    {
        $req = new Request(<<<HTTP
            GET /hello?name=John HTTP/1.1\r
            Accept: text/html\r
            Host: localhost\r
            \r
            HTTP
        );

        $con = new class extends ConnectionInterface
        {
            public Response $buffer;

            #[\Override] public function send($send_buffer)
            {
                $this->buffer = $send_buffer;
            }

            #[\Override] public function getRemoteIp()
            {
                // TODO: Implement getRemoteIp() method.
            }

            #[\Override] public function getRemotePort()
            {
                // TODO: Implement getRemotePort() method.
            }

            #[\Override] public function getRemoteAddress()
            {
                // TODO: Implement getRemoteAddress() method.
            }

            #[\Override] public function getLocalIp()
            {
                // TODO: Implement getLocalIp() method.
            }

            #[\Override] public function getLocalPort()
            {
                // TODO: Implement getLocalPort() method.
            }

            #[\Override] public function getLocalAddress()
            {
                // TODO: Implement getLocalAddress() method.
            }

            #[\Override] public function isIPv4()
            {
                // TODO: Implement isIPv4() method.
            }

            #[\Override] public function isIPv6()
            {
                // TODO: Implement isIPv6() method.
            }

            #[\Override] public function close($data = null)
            {
                // TODO: Implement close() method.
            }
        };

        $this->backend->handle($con, $req);

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
            $con->buffer->rawBody()
        );
    }
}
