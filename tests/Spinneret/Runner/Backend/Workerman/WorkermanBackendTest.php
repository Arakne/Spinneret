<?php

namespace Arakne\Tests\Spinneret\Runner\Backend\Workerman;

use Arakne\Spinneret\Runner\Backend\Workerman\WorkermanBackend;
use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;
use Closure;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionProperty;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

use function curl_exec;
use function curl_init;
use function curl_setopt;
use function pcntl_fork;
use function pcntl_waitpid;
use function sleep;

class WorkermanBackendTest extends TestCase
{
    private TestApplication $app;
    private WorkermanBackend $backend;
    private ?int $lastPid = null;

    protected function setUp(): void
    {
        $this->app = new TestApplication(isDev: true, env: 'test');
        $this->backend = $this->app->get(WorkermanBackend::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->lastPid !== null) {
            posix_kill($this->lastPid, SIGKILL);
            $this->lastPid = null;
        }
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

        $this->assertSame(2, $worker->count);
        $this->assertSame('Spinneret Workerman', $worker->name);
        $this->assertSame('tcp', $worker->transport);

        $r = new ReflectionFunction($worker->onMessage);

        $this->assertSame(WorkermanBackend::class, $r->getClosureCalledClass()->getName());
        $this->assertSame('handle', $r->name);
    }

    #[Test]
    public function startStop()
    {
        $this->backend->init();
        $pid = $this->launchInBackground(fn () => $this->backend->start(true));

        $curl = curl_init('http://127.0.0.1:5123/hello');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        for ($i = 0; $i < 100; ++$i) {
            usleep(100000);
            $response = curl_exec($curl);

            if ($response !== false) {
                break;
            }
        }

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
            $response
        );

        $this->launchInBackground(fn () => $this->backend->stop());
        sleep(1);

        pcntl_waitpid($pid, $status);

        $this->assertFalse(curl_exec($curl));
        $this->assertSame(0, $status);
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

            #[\Override] public function send($send_buffer, bool $raw = false): ?bool
            {
                $this->buffer = $send_buffer;
                return null;
            }

            #[\Override] public function getRemoteIp(): string
            {
                // TODO: Implement getRemoteIp() method.
            }

            #[\Override] public function getRemotePort(): int
            {
                // TODO: Implement getRemotePort() method.
            }

            #[\Override] public function getRemoteAddress(): string
            {
                // TODO: Implement getRemoteAddress() method.
            }

            #[\Override] public function getLocalIp(): string
            {
                // TODO: Implement getLocalIp() method.
            }

            #[\Override] public function getLocalPort(): int
            {
                // TODO: Implement getLocalPort() method.
            }

            #[\Override] public function getLocalAddress(): string
            {
                // TODO: Implement getLocalAddress() method.
            }

            #[\Override] public function isIPv4(): bool
            {
                // TODO: Implement isIPv4() method.
            }

            #[\Override] public function isIPv6(): bool
            {
                // TODO: Implement isIPv6() method.
            }

            #[\Override] public function close($data = null, bool $raw = false): void
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

    private function launchInBackground(Closure $task): int
    {
        $pid = pcntl_fork();

        if ($pid !== 0) {
            return $this->lastPid = $pid;
        }

        $task();
        exit(1);
    }
}
