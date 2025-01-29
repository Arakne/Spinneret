<?php

namespace Arakne\Spinneret\Runner\Backend\Httpd;

use Arakne\Spinneret\Application\Application;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;

use function fastcgi_finish_request;
use function function_exists;
use function header;
use function http_response_code;

/**
 * Backend for application running through an HTTP server like Apache or Nginx.
 * Supports fpm or mod_php.
 */
final readonly class HttpdBackend
{
    public function __construct(
        private Application $application,
        private ServerRequestCreatorInterface $serverRequestCreator,
    ) {}

    /**
     * Run the HTTP request and send the response.
     */
    public function run(): void
    {
        $request = $this->serverRequestCreator->fromGlobals();
        $response = $this->application->handle($request);

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $body = $response->getBody();
        $size = $body->getSize();

        if ($size !== null && $size < 262144) {
            echo $body;
        } else {
            if ($body->isSeekable()) {
                $body->rewind();
            }

            while (!$body->eof()) {
                echo $body->read(8192);
            }
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
