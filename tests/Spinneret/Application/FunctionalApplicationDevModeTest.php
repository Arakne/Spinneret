<?php

namespace Arakne\Tests\Spinneret\Application;

use Arakne\Tests\Spinneret\Application\Fixtures\TestApplication;

class FunctionalApplicationDevModeTest extends FunctionalApplicationTest
{
    protected function createApplication(): TestApplication
    {
        return new TestApplication(true);
    }
}
