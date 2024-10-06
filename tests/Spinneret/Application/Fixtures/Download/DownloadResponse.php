<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Download;

final class DownloadResponse
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,
    ) {
    }
}
