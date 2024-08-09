<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Error;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Presenter\RequestPresenter;
use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Result\NotFound;
use Override;

final class ErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->presenter(NotFound::class, RequestPresenter::class);
        $this->presenter(MethodNotAllowed::class, RequestPresenter::class);

        $this->renderer(NotFound::class, NotFoundRenderer::class);
        $this->renderer(MethodNotAllowed::class, MethodNotAllowedRenderer::class);

        $this->get('/error', RaiseErrorRequest::class, RaiseErrorPresenter::class);
    }
}
