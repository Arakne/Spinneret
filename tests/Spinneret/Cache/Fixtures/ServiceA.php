<?php

namespace Arakne\Tests\Spinneret\Cache\Fixtures;

use Psr\SimpleCache\CacheInterface;

class ServiceA
{
    public function __construct(
        public CacheInterface $cache,
    ) {}
}
