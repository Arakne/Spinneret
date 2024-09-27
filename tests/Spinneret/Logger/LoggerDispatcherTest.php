<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Logger\Driver\ArrayLogger;
use Arakne\Spinneret\Logger\LoggerDispatcher;
use Arakne\Spinneret\Logger\LoggerFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LoggerDispatcherTest extends TestCase
{
    private ArrayLogger $logger1;
    private ArrayLogger $logger2;
    private LoggerDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->logger1 = new ArrayLogger();
        $this->logger2 = new ArrayLogger();

        $this->dispatcher = new LoggerDispatcher(
            new LoggerFilter(
                $this->logger1,
                levelMin: 2,
                levelMax: 5
            ),
            new LoggerFilter(
                $this->logger2,
                contextKeys: ['test']
            ),
        );
    }

    #[Test]
    public function debug()
    {
        $this->dispatcher->debug('message');
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);

        $this->dispatcher->debug('message', ['test' => 'value']);
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'debug',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function info()
    {
        $this->dispatcher->info('message');
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);

        $this->dispatcher->info('message', ['test' => 'value']);
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'info',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function notice()
    {
        $this->dispatcher->notice('message');
        $this->assertCount(1, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'notice',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);

        $this->dispatcher->notice('message', ['test' => 'value']);
        $this->assertCount(2, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'notice',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);
        $this->assertEquals([
            'level' => 'notice',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger1->logs[1]);
        $this->assertEquals([
            'level' => 'notice',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function warning()
    {
        $this->dispatcher->warning('message');
        $this->assertCount(1, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'warning',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);

        $this->dispatcher->warning('message', ['test' => 'value']);
        $this->assertCount(2, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'warning',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);
        $this->assertEquals([
            'level' => 'warning',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger1->logs[1]);
        $this->assertEquals([
            'level' => 'warning',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function error()
    {
        $this->dispatcher->error('message');
        $this->assertCount(1, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'error',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);

        $this->dispatcher->error('message', ['test' => 'value']);
        $this->assertCount(2, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'error',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);
        $this->assertEquals([
            'level' => 'error',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger1->logs[1]);
        $this->assertEquals([
            'level' => 'error',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function critical()
    {
        $this->dispatcher->critical('message');
        $this->assertCount(1, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'critical',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);

        $this->dispatcher->critical('message', ['test' => 'value']);
        $this->assertCount(2, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'critical',
            'message' => 'message',
            'context' => [],
        ], $this->logger1->logs[0]);
        $this->assertEquals([
            'level' => 'critical',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger1->logs[1]);
        $this->assertEquals([
            'level' => 'critical',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function alert()
    {
        $this->dispatcher->alert('message');
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);

        $this->dispatcher->alert('message', ['test' => 'value']);
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'alert',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }

    #[Test]
    public function emergency()
    {
        $this->dispatcher->emergency('message');
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(0, $this->logger2->logs);

        $this->dispatcher->emergency('message', ['test' => 'value']);
        $this->assertCount(0, $this->logger1->logs);
        $this->assertCount(1, $this->logger2->logs);
        $this->assertEquals([
            'level' => 'emergency',
            'message' => 'message',
            'context' => ['test' => 'value'],
        ], $this->logger2->logs[0]);
    }
}
