<?php

namespace Arakne\Tests\Spinneret\Logger\Driver;

use Arakne\Spinneret\Logger\Driver\FileLogger;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FileLoggerTest extends TestCase
{
    #[Test]
    public function functionalLogAndFlush()
    {
        $file = '/tmp/'.bin2hex(random_bytes(8)).'/test.log';
        $logger = new FileLogger($file);

        $time = time();
        $logger->info('test');
        $this->assertFileDoesNotExist($file);

        $logger->flush();

        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertTrue(in_array($content, [
            date('Y-m-d H:i:s', $time).' INFO test'.PHP_EOL,
            date('Y-m-d H:i:s', $time + 1).' INFO test'.PHP_EOL,
        ]), 'Unexpected content: '.$content);
    }

    #[Test]
    public function functionalLogMultipleAndFlush()
    {
        $file = '/tmp/'.bin2hex(random_bytes(8)).'/test.log';
        $logger = new FileLogger($file);

        $logger->info('test');
        $logger->error('error');
        $this->assertFileDoesNotExist($file);

        $logger->flush();

        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertMatchesRegularExpression(<<<'LOG'
            /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} INFO test
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} ERROR error
            $/s
            LOG
            , $content);
    }

    #[Test]
    public function functionalLogShouldFlushOnDestruct()
    {
        $file = '/tmp/'.bin2hex(random_bytes(8)).'/test.log';
        $logger = new FileLogger($file);

        $logger->info('test');
        $logger->error('error');
        $this->assertFileDoesNotExist($file);

        unset($logger);

        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertMatchesRegularExpression(<<<'LOG'
            /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} INFO test
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} ERROR error
            $/s
            LOG
            , $content);
    }

    #[Test]
    public function functionalLogShouldFlushWhenBufferIsFull()
    {
        $file = '/tmp/'.bin2hex(random_bytes(8)).'/test.log';
        $logger = new FileLogger($file, bufferSize: 256);

        $logger->info('test', ['foo' => 'bar']);
        $logger->warning('my warning {test}', ['test' => 'qux']);
        $this->assertFileDoesNotExist($file);

        $logger->debug('my debug');
        $logger->alert('my alert');
        $logger->notice('my notice');
        $logger->emergency('my emergency ----');

        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertMatchesRegularExpression(<<<'LOG'
            /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} INFO test {"foo":"bar"}
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} WARNING my warning qux {"test":"qux"}
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} DEBUG my debug
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} ALERT my alert
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} NOTICE my notice
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} EMERGENCY my emergency ----
            $/s
            LOG
            , $content);
    }

    #[Test]
    public function functionalLogShouldFlushAfter2Seconds()
    {
        $file = '/tmp/'.bin2hex(random_bytes(8)).'/test.log';
        $logger = new FileLogger($file);

        $logger->info('test');
        $this->assertFileDoesNotExist($file);

        sleep(2);
        $logger->error('error');
        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertMatchesRegularExpression(<<<'LOG'
            /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} INFO test
            [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2} ERROR error
            $/s
            LOG
            , $content);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function invalidFileShouldIgnore()
    {
        $logger = new FileLogger('/dev/null');

        $logger->info('test');
        $logger->flush();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function noRight()
    {
        $logger = new FileLogger('/proc/uptime');

        $logger->info('test');
        $logger->flush();
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function invalidDirectoryShouldIgnore()
    {
        $logger = new FileLogger('/dev/null/test.log');

        $logger->info('test');
        $logger->flush();
    }
}
