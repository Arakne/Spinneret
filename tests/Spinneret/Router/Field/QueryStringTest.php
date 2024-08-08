<?php

namespace Arakne\Tests\Spinneret\Router\Field;

use Arakne\Spinneret\Router\Field\QueryString;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{
    #[Test]
    public function extract()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['name' => 'world', 'other' => 'foo']);
        $psrRequest = $psrRequest->withParsedBody(['not_present' => 'bar']);

        $field = new QueryString();

        $this->assertSame('world', $field->extract($psrRequest, 'name'));
        $this->assertNull($field->extract($psrRequest, 'not_found'));
    }

    #[Test]
    public function extractAll()
    {
        $psrRequest = new ServerRequest('GET', '/hello');
        $psrRequest = $psrRequest->withQueryParams(['name' => 'world', 'other' => 'foo']);
        $psrRequest = $psrRequest->withParsedBody(['not_present' => 'bar']);

        $field = new QueryString();

        $this->assertSame(['name' => 'world', 'other' => 'foo'], $field->extractAll($psrRequest));
    }

    #[Test]
    public function compileExtract()
    {
        $field = new QueryString();
        $this->assertSame('$request->getQueryParams()[\'name\'] ?? null', $field->compileExtract('$request', 'name'));
    }

    #[Test]
    public function compileExtractAll()
    {
        $field = new QueryString();
        $this->assertSame('$request->getQueryParams()', $field->compileExtractAll('$request'));
    }
}
