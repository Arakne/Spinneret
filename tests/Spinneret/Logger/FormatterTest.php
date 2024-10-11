<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Logger\Formatter;
use Arakne\Spinneret\Runner\RunnerStepEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class FormatterTest extends TestCase
{
    #[Test]
    public function message()
    {
        $time = 1727367302;

        $this->assertEquals('2024-09-26 18:15:02 INFO message', Formatter::message(LogLevel::INFO, 'message', timestamp: $time));
        $this->assertEquals('2024-09-26 18:15:02 ALERT message', Formatter::message(LogLevel::ALERT, 'message', timestamp: $time));
        $this->assertEquals('2024-09-26 18:15:02 ALERT message {"foo":"bar"}', Formatter::message(LogLevel::ALERT, 'message', ['foo' => 'bar'], timestamp: $time));
        $this->assertEquals('2024-09-26 18:15:02 ALERT message bar {"foo":"bar"}', Formatter::message(LogLevel::ALERT, 'message {{ foo }}', ['foo' => 'bar'], timestamp: $time));
        $this->assertEquals("2024-09-26 18:15:02 ALERT message (object) array(\n) {\"foo\":{}}", Formatter::message(LogLevel::ALERT, 'message {{ foo }}', ['foo' => new \stdClass()], timestamp: $time));
        $this->assertEquals('2024-09-26 18:15:02 ALERT message [a, b] {"foo":["a","b"]}', Formatter::message(LogLevel::ALERT, 'message {{ foo }}', ['foo' => ['a', 'b']], timestamp: $time));
        $this->assertEquals('2024-09-26 18:15:02 ALERT message bar {"0":12,"foo":"bar","1":24}', Formatter::message(LogLevel::ALERT, 'message {{ foo }}', [12, 'foo' => 'bar', 24], timestamp: $time));
    }

    #[Test]
    public function value()
    {
        $this->assertSame('string', Formatter::value('string'));
        $this->assertSame('1', Formatter::value(1));
        $this->assertSame('1.1', Formatter::value(1.1));
        $this->assertSame('true', Formatter::value(true));
        $this->assertSame('false', Formatter::value(false));
        $this->assertSame('', Formatter::value(null));
        $this->assertSame('[]', Formatter::value([]));
        $this->assertSame('[a, b]', Formatter::value(['a', 'b']));
        $this->assertSame('[a, false]', Formatter::value(['a', false]));
        $this->assertSame("array (\n  'foo' => 'bar',\n)", Formatter::value(['foo' => 'bar']));
        $this->assertSame("Router", Formatter::value(RunnerStepEnum::Router));
        $this->assertSame('foo', Formatter::value(new class { public function __toString() { return 'foo'; } }));
    }
}
