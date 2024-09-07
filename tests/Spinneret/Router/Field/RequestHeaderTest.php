<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\RequestHeader;
use BadMethodCallException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestHeaderTest extends TestCase
{
    #[Test]
    public function extract()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withHeader('name', 'world')
            ->withHeader('other', 'foo')
        ;

        $field = new RequestHeader();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertSame('', $field->extract($psrRequest, 'not_found'));

        $field = new RequestHeader('other');
        $this->assertSame('foo', $field->extract($psrRequest, 'name'));
    }

    #[Test]
    public function extractAll()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot extract all headers');

        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withHeader('name', 'world')
            ->withHeader('other', 'foo')
        ;

        $field = new RequestHeader();
        $field->extractAll($psrRequest);
    }

    #[Test]
    public function compileExtract()
    {
        $field = new RequestHeader();
        $this->assertSame('$request->getHeaderLine(\'name\')', $field->compileExtract('$request', 'name'));

        $field = new RequestHeader('other');
        $this->assertSame('$request->getHeaderLine(\'other\')', $field->compileExtract('$request', 'name'));
    }

    #[Test]
    public function compileExtractAll()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot extract all headers');

        $field = new RequestHeader();
        $field->compileExtractAll('$request');
    }
}
