<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\FieldsExtractor;
use Arakne\Tests\Spinneret\Router\Fixtures\HelloRequest;
use Arakne\Tests\Spinneret\Router\Fixtures\MixedFieldsRequest;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class FieldsExtractorTest extends TestCase
{
    public function test_invoke_simple_request()
    {
        $extractor = new FieldsExtractor(HelloRequest::class);
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['name' => 'world', 'other' => 'foo']);
        $psrRequest = $psrRequest->withParsedBody(['not_present' => 'bar']);

        $this->assertSame(
            ['name' => 'world', 'other' => 'foo'],
            $extractor($psrRequest)
        );
    }

    public function test_compile_simple_request()
    {
        $extractor = new FieldsExtractor(HelloRequest::class);

        $code = $extractor->compile('GET');
        $this->assertSame(
            'fn ($request) => $request->getQueryParams()',
            $code
        );

        $callback = eval("return $code;");

        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['name' => 'world', 'other' => 'foo']);
        $psrRequest = $psrRequest->withParsedBody(['not_present' => 'bar']);

        $this->assertSame(
            ['name' => 'world', 'other' => 'foo'],
            $callback($psrRequest)
        );
    }

    public function test_invoke_mixed_fields()
    {
        $extractor = new FieldsExtractor(MixedFieldsRequest::class);
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['key' => 'foo', 'ignored' => 'bar', 'login' => 'ignored']);
        $psrRequest = $psrRequest->withParsedBody(['login' => 'baz', 'password' => 'rab', 'key' => 'ignored']);

        $this->assertSame(
            ['login' => 'baz', 'password' => 'rab', 'key' => 'foo'],
            $extractor($psrRequest)
        );
    }

    public function test_compile_mixed_fields()
    {
        $extractor = new FieldsExtractor(MixedFieldsRequest::class);

        $psrRequest = new ServerRequest('POST', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['key' => 'foo', 'ignored' => 'bar', 'login' => 'ignored']);
        $psrRequest = $psrRequest->withParsedBody(['login' => 'baz', 'password' => 'rab', 'key' => 'ignored']);

        $code = $extractor->compile('POST');
        $this->assertSame(
            'fn ($request) => [\'key\' => $request->getQueryParams()[\'key\'] ?? null, ] + (array) ($request->getParsedBody() ?? [])',
            $code
        );

        $callback = eval("return $code;");

        $this->assertSame(
            ['key' => 'foo', 'login' => 'baz', 'password' => 'rab'],
            $callback($psrRequest)
        );
    }
}
