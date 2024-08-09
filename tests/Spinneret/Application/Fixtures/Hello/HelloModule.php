<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Hello;

use Arakne\Spinneret\Application\AbstractModule;
use Override;

final class HelloModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->get('/hello', HelloRequest::class, HelloPresenter::class);
        $this->renderer(HelloResponse::class, HelloRenderer::class);
    }
}
