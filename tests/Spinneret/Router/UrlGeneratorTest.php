<?php

namespace Arakne\Tests\Spinneret\Router;

use Arakne\Spinneret\Router\Attribute\Route;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\UrlGenerator;
use Arakne\Tests\Spinneret\Application\Fixtures\Registration\RegistrationRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequestPath;
use Arakne\Tests\Spinneret\Router\Fixtures\MappedQueryStringRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MappedRequestPath;
use Arakne\Tests\Spinneret\Router\Fixtures\MappedRequestFields;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsBodyRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsGetRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsPostRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsQueryStringRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\DefaultFormFactory;
use Symfony\Component\Routing\Generator\UrlGenerator as SfUrlGenerator;
use Symfony\Component\Routing\RequestContext;

class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $builder = new RouteCollectionBuilder();
        $builder->get('/hello/{name}', HelloRequestPath::class);
        $builder->get('/mapped/{slug}', MappedRequestPath::class);
        $builder->get('/search', MappedQueryStringRequest::class);
        $builder->post('/mapped-fields', MappedRequestFields::class);
        $builder->get('/register', RegistrationRequest::class);
        $builder->get('/foo-{id}', MixedFieldsGetRequest::class);
        $builder->get('/foo-{id}', MixedFieldsPostRequest::class);
        $builder->get('/foo2-{id}', MixedFieldsQueryStringRequest::class);
        $builder->get('/foo2-{id}', MixedFieldsBodyRequest::class);

        $this->generator = new UrlGenerator(
            new SfUrlGenerator(
                $builder->routes,
                RequestContext::fromUri('http://localhost')
            ),
            DefaultFormFactory::runtime(),
        );
    }

    #[Test]
    public function url()
    {
        $this->assertEquals('http://localhost/register', $this->generator->url(RegistrationRequest::class));
        $this->assertEquals('http://localhost/register?key=aqwzsx', $this->generator->url(RegistrationRequest::class, ['key' => 'aqwzsx']));
        $this->assertEquals('http://localhost/hello/John', $this->generator->url(HelloRequestPath::class, ['name' => 'John']));
        $this->assertEquals('http://localhost/hello/John?other=value', $this->generator->url(HelloRequestPath::class, ['name' => 'John', 'other' => 'value']));
        $this->assertEquals('http://localhost/hello/John?other=value', $this->generator->url(new HelloRequestPath(), ['name' => 'John', 'other' => 'value']));
        $req = new HelloRequestPath();
        $req->name = 'John';
        $this->assertEquals('http://localhost/hello/John', $this->generator->url($req));
        $this->assertEquals('http://localhost/hello/override', $this->generator->url($req, ['name' => 'override']));
    }

    #[Test]
    public function urlShouldFilterExplicitParametersInStrictMode()
    {
        $this->assertSame(
            'http://localhost/hello/John',
            $this->generator->url(
                HelloRequestPath::class,
                ['name' => 'John', 'other' => 'must-not-be-exported'],
                strictParameters: true,
            ),
        );

        $this->assertSame(
            'http://localhost/mapped-fields?search=spinneret',
            $this->generator->url(
                MappedRequestFields::class,
                [
                    'search' => 'spinneret',
                    'body' => 'must-not-be-exported',
                    'other' => 'must-not-be-exported',
                ],
                strictParameters: true,
            ),
        );
    }

    #[Test]
    public function urlShouldMergeObjectParametersBeforeStrictFiltering()
    {
        $request = new MappedRequestFields('from-object', 'must-not-be-exported');

        $this->assertSame(
            'http://localhost/mapped-fields?search=from-object',
            $this->generator->url($request, strictParameters: true),
        );

        $this->assertSame(
            'http://localhost/mapped-fields?search=explicit',
            $this->generator->url(
                $request,
                ['search' => 'explicit', 'other' => 'must-not-be-exported'],
                strictParameters: true,
            ),
        );
    }

    #[Test]
    public function urlShouldIgnoredNonUrlParametersOnGetRequest()
    {
        $req = new MixedFieldsGetRequest(
            id: 42,
            name: 'John',
            user: (object) ['login' => 'bob'],
            referrer: 'https://example.com'
        );

        $this->assertSame('http://localhost/foo-42?name=John', $this->generator->url($req));
    }

    #[Test]
    public function urlShouldMapRequestPathName()
    {
        $this->assertSame(
            'http://localhost/mapped/42',
            $this->generator->url(new MappedRequestPath('42')),
        );
    }

    #[Test]
    public function urlShouldMapQueryStringHttpFieldName()
    {
        $this->assertSame(
            'http://localhost/search?search=spinneret',
            $this->generator->url(new MappedQueryStringRequest('spinneret')),
        );
    }

    #[Test]
    public function urlShouldMapQueryStringName()
    {
        $this->assertSame(
            'http://localhost/mapped-fields?search=spinneret',
            $this->generator->url(new MappedRequestFields('spinneret', 'must-not-be-exported')),
        );
    }

    #[Test]
    public function urlShouldIgnoredNonUrlParametersOnQueryStringRequest()
    {
        $req = new MixedFieldsQueryStringRequest(
            id: 42,
            name: 'John',
            user: (object) ['login' => 'bob'],
            referrer: 'https://example.com'
        );

        $this->assertSame('http://localhost/foo2-42?name=John', $this->generator->url($req));
    }

    #[Test]
    public function urlShouldIgnoredNonUrlParametersOnPostRequest()
    {
        $req = new MixedFieldsPostRequest(
            id: 42,
            name: 'John',
            user: (object) ['login' => 'bob'],
            referrer: 'https://example.com',
            value: 'foo'
        );

        $this->assertSame('http://localhost/foo-42?name=John', $this->generator->url($req));
    }

    #[Test]
    public function urlShouldIgnoredNonUrlParametersOnBodyRequest()
    {
        $req = new MixedFieldsBodyRequest(
            id: 42,
            name: 'John',
            user: (object) ['login' => 'bob'],
            referrer: 'https://example.com',
            value: 'foo'
        );

        $this->assertSame('http://localhost/foo2-42?name=John', $this->generator->url($req));
    }

    #[Test]
    public function urlShouldUseQueryStringByDefaultForRequestRouteMethods()
    {
        $requests = [
            'HEAD' => new #[Route('/query', methods: ['HEAD'])] class {
                public string $name = 'John';
            },
            'OPTIONS' => new #[Route('/query', methods: ['OPTIONS'])] class {
                public string $name = 'John';
            },
            'DELETE' => new #[Route('/query', methods: ['DELETE'])] class {
                public string $name = 'John';
            },
        ];

        foreach ($requests as $method => $request) {
            $builder = new RouteCollectionBuilder();
            $builder->add('/query', $request::class, [$method]);
            $generator = new UrlGenerator(
                new SfUrlGenerator(
                    $builder->routes,
                    RequestContext::fromUri('http://localhost'),
                ),
                DefaultFormFactory::runtime(),
            );

            $this->assertSame('http://localhost/query?name=John', $generator->url($request));
        }
    }
}
