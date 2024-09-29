<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Logger\ContextLogger;
use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContextLoggerTest extends TestCase
{
    #[Test]
    public function withMarker()
    {
        $inner = new ArrayLogger();
        $logger = new ContextLogger($inner, 'marker');

        $logger->info('message');

        $this->assertEquals([
            'level' => 'info',
            'message' => 'marker message',
            'context' => [],
        ], $inner->logs[0]);
    }

    #[Test]
    public function withContext()
    {
        $inner = new ArrayLogger();
        $logger = new ContextLogger($inner, context: ['key' => 'value']);

        $logger->info('message', ['foo' => 'bar']);

        $this->assertEquals([
            'level' => 'info',
            'message' => 'message',
            'context' => ['foo' => 'bar', 'key' => 'value'],
        ], $inner->logs[0]);
    }

    #[Test]
    public function decorateContextWithMarkerAndContext()
    {
        $inner = new ArrayLogger();
        $logger1 = new ContextLogger($inner, marker: 'foo', context: ['key' => 'value']);
        $logger2 = new ContextLogger($logger1, marker: 'bar', context: ['foo' => 'bar']);

        $logger2->info('message', ['baz' => 'qux']);

        $this->assertEquals([
            'level' => 'info',
            'message' => 'bar foo message',
            'context' => ['baz' => 'qux', 'foo' => 'bar', 'key' => 'value'],
        ], $inner->logs[0]);
    }

    #[Test]
    public function decorateContextWithoutMarkerOnFirst()
    {
        $inner = new ArrayLogger();
        $logger1 = new ContextLogger($inner, context: ['key' => 'value']);
        $logger2 = new ContextLogger($logger1, marker: 'bar', context: ['foo' => 'bar']);

        $logger2->info('message', ['baz' => 'qux']);

        $this->assertEquals([
            'level' => 'info',
            'message' => 'bar message',
            'context' => ['baz' => 'qux', 'foo' => 'bar', 'key' => 'value'],
        ], $inner->logs[0]);
    }

    #[Test]
    public function decorateContextWithoutMarkerOnSecondAndContext()
    {
        $inner = new ArrayLogger();
        $logger1 = new ContextLogger($inner, marker: 'foo', context: ['key' => 'value']);
        $logger2 = new ContextLogger($logger1, context: ['foo' => 'bar']);

        $logger2->info('message', ['baz' => 'qux']);

        $this->assertEquals([
            'level' => 'info',
            'message' => 'foo message',
            'context' => ['baz' => 'qux', 'foo' => 'bar', 'key' => 'value'],
        ], $inner->logs[0]);
    }

    #[Test]
    public function decorateContextWithoutMarker()
    {
        $inner = new ArrayLogger();
        $logger1 = new ContextLogger($inner, context: ['key' => 'value']);
        $logger2 = new ContextLogger($logger1, context: ['foo' => 'bar']);

        $logger2->info('message', ['baz' => 'qux']);

        $this->assertEquals([
            'level' => 'info',
            'message' => 'message',
            'context' => ['baz' => 'qux', 'foo' => 'bar', 'key' => 'value'],
        ], $inner->logs[0]);
    }
}
