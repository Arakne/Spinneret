<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Tests\Spinneret\Fixtures\TestApplication;

class FunctionalApplicationDevModeTest extends FunctionalApplicationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new TestApplication(true);
    }
}
