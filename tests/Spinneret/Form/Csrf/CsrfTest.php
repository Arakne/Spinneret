<?php

namespace Arakne\Tests\Spinneret\Form\Csrf;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Application\ModuleInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Form\Csrf\Csrf;
use Arakne\Spinneret\Form\Csrf\CsrfTokenParameters;
use Arakne\Spinneret\Form\FormModule;
use Arakne\Spinneret\Presenter\Attribute\Presenter;
use Arakne\Spinneret\Router\RouteCollectionBuilder;
use Arakne\Spinneret\Router\RouteConfiguratorInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Security\SecurityModule;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Arakne\Spinneret\View\Attribute\Renderer;
use Arakne\Tests\Spinneret\Form\Fixtures\BasicCsrfForm;
use Arakne\Tests\Spinneret\Form\Fixtures\JsonStubRenderer;
use Arakne\Tests\Spinneret\Form\Fixtures\PresenterStub;
use BadMethodCallException;
use Nyholm\Psr7\ServerRequest;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Quatrevieux\Form\Validator\FieldError;
use Quatrevieux\Form\View\FieldView;
use stdClass;

class CsrfTest extends TestCase
{
    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function functional(bool $isDev): void
    {
        $app = new class(isDev: $isDev, env: 'test') extends Application {
            public function configDir(): string
            {
                return __DIR__ . '/Fixtures/config';
            }

            protected function applicationModules(): array
            {
                return [
                    new FormModule(),
                    new SecurityModule(),
                    new class implements ModuleInterface, RouteConfiguratorInterface {
                        #[Override]
                        public function register(ContainerBuilder $containerBuilder): void
                        {
                            $containerBuilder->register(PresenterStub::class)->tag(new Presenter(BasicCsrfForm::class));
                            $containerBuilder->register(JsonStubRenderer::class)->tag(new Renderer(stdClass::class));
                        }

                        #[Override]
                        public function configureRoutes(RouteCollectionBuilder $builder): void
                        {
                            $builder->post('/csrf', BasicCsrfForm::class);
                        }
                    },
                ];
            }
        };

        PresenterStub::$handleError = function (object $o, RoutedRequest $r) {
            return (object) [
                'errors' => $r->form->errors(),
                'token' => $r->form->view()['csrf']->value,
            ];
        };

        PresenterStub::$handleSuccess = function (object $o, RoutedRequest $r) {
            return (object) [
                'success' => true,
                'token' => $r->form->view()['csrf']->value,
            ];
        };

        // First request without token : will generate a new token
        $req = new ServerRequest('POST', '/csrf');
        $response = $app->handle($req);
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals([
            'errors' => [
                'csrf' => [
                    'code' => '642ecf60-c56e-547b-9064-dd30d553f5dd',
                    'message' => 'Invalid CSRF token',
                ],
            ],
            'token' => $body['token'],
        ], json_decode((string) $response->getBody(), true));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $body['token']);

        // New session = new token
        $req = new ServerRequest('POST', '/csrf');
        $response = $app->handle($req);
        $newBody = json_decode((string) $response->getBody(), true);
        $this->assertNotEquals($body['token'], $newBody['token']);

        // Request with valid token
        $authCookie = explode('=', explode(';', $response->getHeaderLine('Set-Cookie'), 2)[0], 2)[1];
        $req = new ServerRequest('POST', '/csrf');
        $req = $req->withCookieParams(['auth' => $authCookie]);
        $req = $req->withParsedBody(['csrf' => $newBody['token']]);
        $response = $app->handle($req);
        $this->assertEquals([
            'success' => true,
            'token' => $newBody['token'],
        ], json_decode((string) $response->getBody(), true));

        // Request with invalid token
        $req = new ServerRequest('POST', '/csrf');
        $req = $req->withCookieParams(['auth' => $authCookie]);
        $req = $req->withParsedBody(['csrf' => 'invalid']);
        $response = $app->handle($req);
        $this->assertEquals([
            'errors' => [
                'csrf' => [
                    'code' => '642ecf60-c56e-547b-9064-dd30d553f5dd',
                    'message' => 'Invalid CSRF token',
                ],
            ],
            'token' => $newBody['token'],
        ], json_decode((string) $response->getBody(), true));
    }

    #[Test]
    public function extract()
    {
        $csrf = new Csrf(key: 'foo');
        $req = new ServerRequest('POST', '/csrf');

        $cookie = new ParsedCookie(
            token: 'a',
            creation: 0,
            expiration: 0,
            version: 1,
            data: null,
        );

        $this->assertNull($csrf->extract($req, 'csrf'));
        $req = $req->withAttribute(ParsedCookie::class, $cookie);
        $this->assertEquals(new CsrfTokenParameters('foo', 'a', null), $csrf->extract($req, 'csrf'));

        $req = $req->withParsedBody(['csrf' => 123]);
        $this->assertNull($csrf->extract($req, 'csrf'));

        $req = $req->withParsedBody(['csrf' => 'input']);
        $this->assertEquals(new CsrfTokenParameters('foo', 'a', 'input'), $csrf->extract($req, 'csrf'));

        $req = $req->withAttribute(ParsedCookie::class, new \stdClass());
        $this->assertNull($csrf->extract($req, 'csrf'));

        $csrf = new Csrf(key: 'foo', parsedCookieAttribute: 'other');
        $req = $req->withAttribute(ParsedCookie::class, $cookie);
        $this->assertNull($csrf->extract($req, 'csrf'));

        $req = $req->withAttribute('other', $cookie);
        $this->assertEquals(new CsrfTokenParameters('foo', 'a', 'input'), $csrf->extract($req, 'csrf'));
    }

    #[Test]
    public function extractAllError()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Csrf must be used on a single property');

        $csrf = new Csrf(key: 'foo');
        $req = new ServerRequest('POST', '/csrf');

        $csrf->extractAll($req);
    }

    #[Test]
    public function compileExtractAllError()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Csrf must be used on a single property');

        $csrf = new Csrf(key: 'foo');
        $csrf->compileExtractAll('$req');
    }

    #[Test]
    public function compileExtract()
    {
        $csrf = new Csrf(key: 'foo');
        $this->assertEquals("Arakne\Spinneret\Form\Csrf\Csrf::extractImpl(\$request, 'csrf', 'foo', 'Arakne\\\Spinneret\\\Security\\\Serializer\\\ParsedCookie')", $csrf->compileExtract('$request', 'csrf'));

        $csrf = new Csrf(key: 'foo', parsedCookieAttribute: 'other');
        $this->assertEquals("Arakne\Spinneret\Form\Csrf\Csrf::extractImpl(\$request, 'csrf', 'foo', 'other')", $csrf->compileExtract('$request', 'csrf'));
    }

    #[Test]
    public function validate()
    {
        $csrf = new Csrf(key: 'foo');

        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, null, new \stdClass()));
        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, new \stdClass(), new \stdClass()));
        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, new CsrfTokenParameters('foo', 'bar', null), new \stdClass()));
        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, new CsrfTokenParameters('foo', 'bar', 'a47933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741852'), new \stdClass()));
        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, new CsrfTokenParameters('foo2', 'bar', '147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851'), new \stdClass()));
        $this->assertEquals(new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), $csrf->validate($csrf, new CsrfTokenParameters('foo', 'bar2', '147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851'), new \stdClass()));

        $this->assertNull($csrf->validate($csrf, new CsrfTokenParameters('foo', 'bar', '147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851'), new \stdClass()));
    }

    #[Test]
    public function view()
    {
        $csrf = new Csrf(key: 'foo');

        $view = $csrf->view($csrf, 'csrf', new CsrfTokenParameters('foo', 'bar', null), null, []);
        $this->assertInstanceOf(FieldView::class, $view);
        $this->assertSame(['type' => 'hidden'], $view->attributes);
        $this->assertSame('147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851', $view->value);
        $this->assertSame('csrf', $view->name);
        $this->assertNull($view->error);

        $view = $csrf->view($csrf, 'csrf', new CsrfTokenParameters('foo', 'bar', null), null, ['type' => 'text', 'foo' => 'bar']);
        $this->assertInstanceOf(FieldView::class, $view);
        $this->assertSame(['type' => 'text', 'foo' => 'bar'], $view->attributes);
        $this->assertSame('147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851', $view->value);
        $this->assertSame('csrf', $view->name);
        $this->assertNull($view->error);

        $view = $csrf->view($csrf, 'csrf', new CsrfTokenParameters('foo', 'bar', null), $e = new FieldError('Invalid CSRF token', code: '642ecf60-c56e-547b-9064-dd30d553f5dd'), []);
        $this->assertInstanceOf(FieldView::class, $view);
        $this->assertSame(['type' => 'hidden'], $view->attributes);
        $this->assertSame('147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851', $view->value);
        $this->assertSame('csrf', $view->name);
        $this->assertSame($e, $view->error);

        $view = $csrf->view($csrf, 'csrf', 'invalid', null, []);
        $this->assertInstanceOf(FieldView::class, $view);
        $this->assertSame(['type' => 'hidden'], $view->attributes);
        $this->assertSame(null, $view->value);
        $this->assertSame('csrf', $view->name);
        $this->assertNull($view->error);

        $view = $csrf->view($csrf, 'csrf', new CsrfTokenParameters('foo', 'bar', null), [$e], []);
        $this->assertInstanceOf(FieldView::class, $view);
        $this->assertSame(['type' => 'hidden'], $view->attributes);
        $this->assertSame('147933218aaabc0b8b10a2b3a5c34684c8d94341bcf10a4736dc7270f7741851', $view->value);
        $this->assertSame('csrf', $view->name);
        $this->assertNull($view->error);
    }
}
