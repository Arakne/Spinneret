<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Logger\LoggerFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class LoggerFilterTest extends TestCase
{
    #[Test]
    public function checkNoFilters()
    {
        $filter = new LoggerFilter(new NullLogger());

        $this->assertTrue($filter->match(3, 'message', []));
        $this->assertTrue($filter->match(0, 'message', []));
        $this->assertTrue($filter->match(100, 'message', []));
    }

    #[Test]
    public function checkWithLevelFilter()
    {
        $filter = new LoggerFilter(new NullLogger(), levelMin: 2, levelMax: 5);
        $this->assertFalse($filter->match(1, 'message', []));
        $this->assertTrue($filter->match(2, 'message', []));
        $this->assertTrue($filter->match(3, 'message', []));
        $this->assertTrue($filter->match(4, 'message', []));
        $this->assertTrue($filter->match(5, 'message', []));
        $this->assertFalse($filter->match(6, 'message', []));
        $this->assertFalse($filter->match(10, 'message', []));

        $filter = new LoggerFilter(new NullLogger(), levelMin: 2);
        $this->assertFalse($filter->match(1, 'message', []));
        $this->assertTrue($filter->match(2, 'message', []));
        $this->assertTrue($filter->match(3, 'message', []));
        $this->assertTrue($filter->match(4, 'message', []));
        $this->assertTrue($filter->match(5, 'message', []));
        $this->assertTrue($filter->match(6, 'message', []));
        $this->assertTrue($filter->match(10, 'message', []));

        $filter = new LoggerFilter(new NullLogger(), levelMax: 5);
        $this->assertTrue($filter->match(1, 'message', []));
        $this->assertTrue($filter->match(2, 'message', []));
        $this->assertTrue($filter->match(3, 'message', []));
        $this->assertTrue($filter->match(4, 'message', []));
        $this->assertTrue($filter->match(5, 'message', []));
        $this->assertFalse($filter->match(6, 'message', []));
        $this->assertFalse($filter->match(10, 'message', []));
    }

    #[Test]
    public function checkWithContextKey()
    {
        $filter = new LoggerFilter(new NullLogger(), contextKeys: ['foo', 'bar']);

        $this->assertFalse($filter->match(3, 'message', []));
        $this->assertFalse($filter->match(3, 'message', ['foo' => 'bar']));
        $this->assertFalse($filter->match(3, 'message', ['foo' => 'bar', 'baz' => 'qux']));
        $this->assertTrue($filter->match(3, 'message', ['foo' => 'bar', 'bar' => 'baz']));
    }

    #[Test]
    public function checkWithCustomFilter()
    {
        $filter = new LoggerFilter(new NullLogger(), filter: fn ($level, $message, $context) => str_contains($message, 'foo'));

        $this->assertFalse($filter->match(3, 'message', []));
        $this->assertTrue($filter->match(3, 'message foo', []));
    }

    #[Test]
    public function logLevelToInt()
    {
        $this->assertSame(0, LoggerFilter::levelToInt(LogLevel::DEBUG));
        $this->assertSame(1, LoggerFilter::levelToInt(LogLevel::INFO));
        $this->assertSame(2, LoggerFilter::levelToInt(LogLevel::NOTICE));
        $this->assertSame(3, LoggerFilter::levelToInt(LogLevel::WARNING));
        $this->assertSame(4, LoggerFilter::levelToInt(LogLevel::ERROR));
        $this->assertSame(5, LoggerFilter::levelToInt(LogLevel::CRITICAL));
        $this->assertSame(6, LoggerFilter::levelToInt(LogLevel::ALERT));
        $this->assertSame(7, LoggerFilter::levelToInt(LogLevel::EMERGENCY));
        $this->assertSame(1, LoggerFilter::levelToInt('foo'));
        $this->assertSame(5, LoggerFilter::levelToInt(5));
        $this->assertSame(1, LoggerFilter::levelToInt(0.02));
    }
}
