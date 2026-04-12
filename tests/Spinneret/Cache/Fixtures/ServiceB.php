<?php

namespace Arakne\Tests\Spinneret\Cache\Fixtures;

use Arakne\Spinneret\Cache\CacheFetcher;
use Psr\SimpleCache\CacheInterface;

class ServiceB implements MarkerInterface
{
    public function __construct(
        public CacheInterface $cache,
        public CacheFetcher $fetcher,
    ) {}
}
