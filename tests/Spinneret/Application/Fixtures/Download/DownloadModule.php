<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

use Arakne\Spinneret\Application\AbstractModule;
use Override;

final class DownloadModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->get('/download', DownloadRequest::class, DownloadPresenter::class);
        $this->renderer(DownloadResponse::class, DownloadRenderer::class);
    }
}
