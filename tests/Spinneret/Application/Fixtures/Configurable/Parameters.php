<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

readonly class Parameters
{
    public function __construct(
        public bool $isDev,
        public string $projectDir,
        public string $logDir,
        public string $cacheDir,
        public string $configDir,
    ) {
    }
}
