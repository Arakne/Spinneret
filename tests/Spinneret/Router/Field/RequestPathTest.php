<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\RequestPath;
use BadMethodCallException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestPathTest extends TestCase
{
    #[Test]
    public function extract()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withAttribute('name', 'world')
            ->withAttribute('other', 'foo')
        ;

        $field = new RequestPath();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertNull($field->extract($psrRequest, 'not_found'));

        $field = new RequestPath('other');
        $this->assertSame('foo', $field->extract($psrRequest, 'name'));
    }

    #[Test]
    public function extractAll()
    {
        $this->expectException(BadMethodCallException::class);
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withAttribute('name', 'world')
            ->withAttribute('other', 'foo')
        ;

        $field = new RequestPath();
        $field->extractAll($psrRequest);
    }

    #[Test]
    public function compileExtract()
    {
        $field = new RequestPath();
        $this->assertSame('$request->getAttribute(\'name\')', $field->compileExtract('$request', 'name'));

        $field = new RequestPath('other');
        $this->assertSame('$request->getAttribute(\'other\')', $field->compileExtract('$request', 'name'));
    }

    #[Test]
    public function compileExtractAll()
    {
        $this->expectException(BadMethodCallException::class);
        $field = new RequestPath();
        $field->compileExtractAll('$request');
    }
}
