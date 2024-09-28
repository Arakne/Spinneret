<?php

namespace Arakne\Tests\Spinneret\Error;

use Arakne\Spinneret\Error\ErrorConfiguration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ErrorConfigurationTest extends TestCase
{
    #[Test]
    public function isIgnored()
    {
        $config = new ErrorConfiguration(errorReportingLevel: E_ALL & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_DEPRECATED);

        $this->assertTrue($config->isIgnored(E_NOTICE));
        $this->assertTrue($config->isIgnored(E_USER_DEPRECATED));
        $this->assertTrue($config->isIgnored(E_DEPRECATED));
        $this->assertFalse($config->isIgnored(E_ERROR));
        $this->assertFalse($config->isIgnored(E_WARNING));
    }

    #[Test]
    public function shouldConvertToException()
    {
        $config = new ErrorConfiguration(convertErrorsToExceptions: E_ALL & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_DEPRECATED);

        $this->assertFalse($config->shouldConvertToException(E_NOTICE));
        $this->assertFalse($config->shouldConvertToException(E_USER_DEPRECATED));
        $this->assertFalse($config->shouldConvertToException(E_DEPRECATED));
        $this->assertTrue($config->shouldConvertToException(E_ERROR));
        $this->assertTrue($config->shouldConvertToException(E_WARNING));
    }
}
