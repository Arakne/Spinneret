<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Spinneret\Application\AbstractConfigurableModule;
use Arakne\Spinneret\Application\Application;
use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\TestConfig;
use http\Message;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractConfigurableModuleTest extends TestCase
{
    #[Test]
    public function immutable()
    {
        $module = TestingAbstractConfigurableModule::create(new Application());

        $this->assertSame($module->configuration(), $module->configuration());
        $this->assertEquals(new TestConfig(), $module->configuration());

        $configured = $module->withConfiguration($conf = new TestConfig(message: 'aaa'));

        $this->assertSame($conf, $configured->configuration());
        $this->assertNotEquals($conf, $module->configuration());
        $this->assertNotSame($configured, $module);
    }
}


class TestingAbstractConfigurableModule extends AbstractConfigurableModule
{
    #[Override]
    protected static function defaultConfiguration(Application $app): object
    {
        return new TestConfig();
    }

    #[Override]
    protected function configure(): void
    {
    }
}
