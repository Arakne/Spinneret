<?php

namespace Arakne\Tests\Spinneret\Cache;

use Arakne\Spinneret\Cache\CacheFetcher;
use Arakne\Spinneret\Cache\Driver\MemoryCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CacheFetcherTest extends TestCase
{
    #[Test]
    public function fetch()
    {
        $cache = new MemoryCache();
        $fetcher = new CacheFetcher($cache);

        $this->assertSame('value', $fetcher->fetch('key', fn() => 'value'));
        $this->assertSame('value', $cache->get('key'));
        $this->assertSame('value', $fetcher->fetch('key', fn() => 'other'));
    }

    #[Test]
    public function fetchWithArrayKey()
    {
        $cache = new MemoryCache();
        $fetcher = new CacheFetcher($cache);

        $key = ['key1', 'key2'];

        $this->assertSame('value', $fetcher->fetch($key, fn() => 'value'));
        $this->assertSame('value', $cache->get('key1.key2'));
        $this->assertSame('value', $fetcher->fetch($key, fn() => 'other'));
    }

    #[Test]
    public function fetchWithInvalidKeyShouldBeCleaned()
    {
        $cache = new MemoryCache();
        $fetcher = new CacheFetcher($cache);

        $key = '@()/';

        $this->assertSame('value', $fetcher->fetch($key, fn() => 'value'));
        $this->assertSame('value', $cache->get('____'));
        $this->assertSame('value', $fetcher->fetch($key, fn() => 'other'));
    }
}
