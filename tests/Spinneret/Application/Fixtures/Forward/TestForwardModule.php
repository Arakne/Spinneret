<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Forward;

use Arakne\Spinneret\Application\AbstractModule;

class TestForwardModule extends AbstractModule
{
    #[\Override] protected function configure(): void
    {
        $this->get('/forward', TestForwardRequest::class, TestForwardPresenter::class);
        $this->renderer(TestForwardResponse::class, TestForwardRenderer::class);
    }
}
