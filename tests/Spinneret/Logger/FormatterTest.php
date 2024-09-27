<?php

namespace Arakne\Tests\Spinneret\Logger;

use Arakne\Spinneret\Logger\Formatter;
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
    }
}
