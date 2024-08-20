<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\QueryString;
use Arakne\Spinneret\Router\Field\RequestBody;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestBodyTest extends TestCase
{
    #[Test]
    public function extract()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['not_present' => 'bar']);
        $psrRequest = $psrRequest->withParsedBody(['name' => 'world', 'other' => 'foo']);

        $field = new RequestBody();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertNull($field->extract($psrRequest, 'not_found'));
        $this->assertNull($field->extract($psrRequest->withParsedBody(null), 'not_found'));
        $this->assertSame('foo', $field->extract($psrRequest->withParsedBody((object) ['name' => 'foo']), 'name'));
    }

    #[Test]
    public function extractWithObject()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withParsedBody((object) ['name' => 'world', 'other' => 'foo']);

        $field = new RequestBody();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertNull($field->extract($psrRequest, 'not_found'));
    }

    #[Test]
    public function extractAll()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['not_present' => 'bar']);
        $psrRequest = $psrRequest->withParsedBody(['name' => 'world', 'other' => 'foo']);

        $field = new RequestBody();

        $this->assertSame(['name' => 'world', 'other' => 'foo'], $field->extractAll($psrRequest));
        $this->assertSame([], $field->extractAll($psrRequest->withParsedBody(null)));
    }

    #[Test]
    public function extractAllWithObject()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['not_present' => 'bar']);
        $psrRequest = $psrRequest->withParsedBody((object) ['name' => 'world', 'other' => 'foo']);

        $field = new RequestBody();

        $this->assertSame(['name' => 'world', 'other' => 'foo'], $field->extractAll($psrRequest));
        $this->assertSame([], $field->extractAll($psrRequest->withParsedBody(null)));
    }

    #[Test]
    public function compileExtract()
    {
        $field = new RequestBody();
        $this->assertSame('((array) $request->getParsedBody())[\'name\'] ?? null', $field->compileExtract('$request', 'name'));
    }

    #[Test]
    public function compileExtractAll()
    {
        $field = new RequestBody();
        $this->assertSame('(array) ($request->getParsedBody() ?? [])', $field->compileExtractAll('$request'));
    }
}
