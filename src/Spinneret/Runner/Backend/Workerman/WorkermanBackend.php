<?php

namespace Arakne\Spinneret\Runner\Backend\Workerman;

use Arakne\Spinneret\Application\Application;
use Nyholm\Psr7\ServerRequest;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

use function function_exists;
use function opcache_get_status;

/**
 * Configure and run the Workerman backend
 */
final class WorkermanBackend
{
    private ?Worker $worker = null;

    public function __construct(
        private readonly Application $application,
        private readonly WorkermanConfig $config,
    ) {}

    /**
     * Check if the JIT is actually enabled
     *
     * To take full advantage of Workerman, the JIT should be enabled
     *
     * @return bool True if the JIT is enabled, false otherwise
     */
    public function checkJit(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        $status = opcache_get_status();

        if ($status === false) {
            return false;
        }

        /** @var array{enable: bool, on: bool, ...array<string, scalar>}|null $jit */
        $jit = $status['jit'] ?? null;

        if ($jit === null) {
            return false;
        }

        return $jit['enabled'] && $jit['on'];
    }

    /**
     * Configure the HTTP Worker
     */
    public function init(): void
    {
        $config = $this->config;
        $workerman = new Worker("http://{$config->host}:{$config->port}");

        $workerman->count = $config->processes;
        $workerman->name = $config->name;
        $workerman->onMessage = $this->handle(...);
        $workerman->reusePort = true;

        $this->worker = $workerman;
        Worker::$pidFile = $config->pidFile;
    }

    /**
     * Start the Workerman process
     *
     * If {@see init()} was not called before, it will be called
     * This method will block the execution
     *
     * @param bool $daemon If true, the process will be detached
     */
    public function start(bool $daemon = false): void
    {
        if ($this->worker === null) {
            $this->init();
        }

        Worker::$logFile = $this->config->logFile;

        global $argv;
        $argv = [$argv[0], 'start'];

        if ($daemon) {
            $argv[] = '-d';
        }

        Worker::runAll();
    }

    /**
     * Stop all Workerman processes
     *
     * If {@see init()} was not called before, it will be called
     * This method will kill all the processes, so no operation should be done after it
     */
    public function stop(): void
    {
        if ($this->worker === null) {
            $this->init();
        }

        global $argv;
        $argv = [$argv[0], 'stop'];

        Worker::runAll();
    }

    /**
     * Handle Workerman HTTP request
     *
     * @param ConnectionInterface $connection
     * @param Request $request
     *
     * @return void
     *
     * @psalm-suppress MixedArgument
     * @psalm-suppress PossiblyNullArgument
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function handle(ConnectionInterface $connection, Request $request): void
    {
        $uri = ($this->config->secure ? 'https://' : 'http://') . (string) $request->header('host', '127.0.0.1') . $request->uri();

        // PSR interfaces doesn't allow to easily create the server request
        // So use directly the Nyholm implementation
        $psrRequest = new ServerRequest(
            $request->method(),
            $uri,
            $request->header(),
            $request->rawBody(),
            $request->protocolVersion()
        );

        $psrRequest = $psrRequest->withQueryParams($request->get());
        $psrRequest = $psrRequest->withParsedBody($request->post());
        $psrRequest = $psrRequest->withCookieParams($request->cookie());

        $psrResponse = $this->application->handle($psrRequest);

        $response = new Response(
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders(),
            (string) $psrResponse->getBody(), // @todo: stream the body if possible
        );

        $connection->send($response);
    }
}
