<?php

namespace Arakne\Tests\Spinneret\Presenter;

use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\RoutedRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestPresenterTest extends TestCase
{
    #[Test]
    public function handleSuccess()
    {
        $presenter = new RequestPresenter();
        $request = new \stdClass();
        $routedRequest = new RoutedRequest(new ServerRequest('GET', '/'), $request);

        $this->assertSame($request, $presenter->handleSuccess($request, $routedRequest));
    }

    #[Test]
    public function handleError()
    {
        $presenter = new RequestPresenter();
        $request = new \stdClass();
        $routedRequest = new RoutedRequest(new ServerRequest('GET', '/'), $request);

        $this->assertSame($request, $presenter->handleError($request, $routedRequest));
    }
}
