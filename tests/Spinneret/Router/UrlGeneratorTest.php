<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\UrlGenerator;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\RegistrationRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGenerator as SfUrlGenerator;
use Symfony\Component\Routing\RequestContext;

class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $builder = new RouteCollectionBuilder();
        $builder->get('/hello/{name}', HelloRequest::class);
        $builder->get('/register', RegistrationRequest::class);

        $this->generator = new UrlGenerator(
            new SfUrlGenerator(
                $builder->routes,
                RequestContext::fromUri('http://localhost')
            )
        );
    }

    #[Test]
    public function url()
    {
        $this->assertEquals('http://localhost/register', $this->generator->url(RegistrationRequest::class));
        $this->assertEquals('http://localhost/register?key=aqwzsx', $this->generator->url(RegistrationRequest::class, ['key' => 'aqwzsx']));
        $this->assertEquals('http://localhost/hello/John', $this->generator->url(HelloRequest::class, ['name' => 'John']));
        $this->assertEquals('http://localhost/hello/John?other=value', $this->generator->url(HelloRequest::class, ['name' => 'John', 'other' => 'value']));
        $this->assertEquals('http://localhost/hello/John?other=value', $this->generator->url(new HelloRequest(), ['name' => 'John', 'other' => 'value']));
        $req = new HelloRequest();
        $req->name = 'John';
        $this->assertEquals('http://localhost/hello/John', $this->generator->url($req));
        $this->assertEquals('http://localhost/hello/override', $this->generator->url($req, ['name' => 'override']));
    }
}
