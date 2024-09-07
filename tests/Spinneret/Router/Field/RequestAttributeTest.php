<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\RequestAttribute;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestAttributeTest extends TestCase
{
    #[Test]
    public function extract()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withAttribute('name', 'world')
            ->withAttribute('other', 'foo')
        ;

        $field = new RequestAttribute();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertNull($field->extract($psrRequest, 'not_found'));

        $field = new RequestAttribute('other');
        $this->assertSame('foo', $field->extract($psrRequest, 'name'));
    }

    #[Test]
    public function extractAll()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest
            ->withAttribute('name', 'world')
            ->withAttribute('other', 'foo')
        ;

        $field = new RequestAttribute();

        $this->assertSame(['name' => 'world', 'other' => 'foo'], $field->extractAll($psrRequest));
    }

    #[Test]
    public function compileExtract()
    {
        $field = new RequestAttribute();
        $this->assertSame('$request->getAttribute(\'name\')', $field->compileExtract('$request', 'name'));

        $field = new RequestAttribute('other');
        $this->assertSame('$request->getAttribute(\'other\')', $field->compileExtract('$request', 'name'));
    }

    #[Test]
    public function compileExtractAll()
    {
        $field = new RequestAttribute();
        $this->assertSame('$request->getAttributes()', $field->compileExtractAll('$request'));
    }
}
