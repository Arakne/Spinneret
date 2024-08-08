<?php

namespace Arakne\Spinneret\Error;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Runner\InternalServerError;
use Override;

/**
 * Register basic error handling functionality.
 */
final class ErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->presenter(InternalServerError::class, ErrorPresenter::class);
        $this->renderer(InternalServerError::class, InternalServerErrorRenderer::class);
    }
}
