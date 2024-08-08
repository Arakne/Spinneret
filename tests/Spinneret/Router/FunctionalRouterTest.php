<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Router;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsRequest;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class FunctionalRouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $builder = new RouteCollectionBuilder();
        $builder->get('/hello', HelloRequest::class);
        $builder->post('/mixed', MixedFieldsRequest::class);

        $this->router = new Router(
            new UrlMatcher($builder->routes, new RequestContext()),
            DefaultFormFactory::runtime()
        );
    }

    public function test_simple()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $resolved = $this->router->request($psrRequest);

        $this->assertTrue($resolved->success);
        $this->assertInstanceOf(HelloRequest::class, $resolved->routedRequest);
        $this->assertEquals('world', $resolved->routedRequest->name);
    }

    public function test_simple_with_parameters()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['name' => 'John']);
        $resolved = $this->router->request($psrRequest);

        $this->assertTrue($resolved->success);
        $this->assertInstanceOf(HelloRequest::class, $resolved->routedRequest);
        $this->assertEquals('John', $resolved->routedRequest->name);
    }

    public function test_with_post_and_get_fields()
    {
        $psrRequest = new ServerRequest('POST', '/mixed');
        $psrRequest = $psrRequest->withQueryParams([
            'key' => '0123456789',
            'login' => 'ignored',
            'password' => 'ignored',
        ]);

        $psrRequest = $psrRequest->withParsedBody([
            'login' => 'john.doe',
            'password' => '$secret$',
            'key' => 'ignored',
        ]);

        $resolved = $this->router->request($psrRequest);

        $this->assertTrue($resolved->success);
        $this->assertInstanceOf(MixedFieldsRequest::class, $resolved->routedRequest);
        $this->assertEquals('0123456789', $resolved->routedRequest->key);
        $this->assertEquals('john.doe', $resolved->routedRequest->login);
        $this->assertEquals('$secret$', $resolved->routedRequest->password);
    }

    public function test_with_bad_method()
    {
        $psrRequest = new ServerRequest('GET', '/mixed');
        $psrRequest = $psrRequest->withQueryParams(['key' => '0123456789']);
        $psrRequest = $psrRequest->withParsedBody(['login' => 'john.doe']);

        $resolved = $this->router->request($psrRequest);

        $this->assertFalse($resolved->success);
        $this->assertInstanceOf(MethodNotAllowed::class, $resolved->routedRequest);
        $this->assertEquals(['POST'], $resolved->routedRequest->allowedMethods);
        $this->assertEquals('GET', $resolved->routedRequest->currentMethod);
    }

    public function test_with_form_error()
    {
        $psrRequest = new ServerRequest('POST', '/mixed');
        $psrRequest = $psrRequest->withQueryParams(['key' => '123']);
        $psrRequest = $psrRequest->withParsedBody([
            'login' => 'john.doe',
            'password' => '$secret$',
        ]);

        $resolved = $this->router->request($psrRequest);

        $this->assertFalse($resolved->success);
        $this->assertInstanceOf(MixedFieldsRequest::class, $resolved->routedRequest);
        $this->assertEquals('123', $resolved->routedRequest->key);
        $this->assertEquals('john.doe', $resolved->routedRequest->login);
        $this->assertEquals('$secret$', $resolved->routedRequest->password);

        $this->assertEquals(['key'], array_keys($resolved->form->errors()));
        $this->assertEquals('The value length is invalid. It should be between 10 and 10 characters long.', $resolved->form->errors()['key']->localizedMessage());
    }
}
