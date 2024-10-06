<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @implements ResponseConfiguratorInterface<DownloadResponse>
 */
final class DownloadRenderer implements ResponseConfiguratorInterface
{
    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Content-Disposition', 'attachment; filename="' . $data->filename . '"')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withBody($this->streamFactory->createStream($data->content))
        ;
    }
}
