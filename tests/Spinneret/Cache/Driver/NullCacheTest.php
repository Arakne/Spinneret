<?php

namespace Arakne\Tests\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Cache\Driver\NullCache;
use ArrayIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NullCacheTest extends TestCase
{
    #[Test]
    public function instance()
    {
       $this->assertInstanceOf(NullCache::class, NullCache::instance());
       $this->assertSame(NullCache::instance(), NullCache::instance());
    }

    #[Test]
    public function get()
    {
        $this->assertSame(42, NullCache::instance()->get('foo', 42));
        $this->assertSame(null, NullCache::instance()->get('foo'));
    }

    #[Test]
    public function set()
    {
        $cache = NullCache::instance();
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertSame(null, $cache->get('foo'));
    }

    #[Test]
    public function delete()
    {
        $cache = NullCache::instance();
        $this->assertTrue($cache->delete('foo'));
    }

    #[Test]
    public function clear()
    {
        $cache = NullCache::instance();
        $this->assertTrue($cache->clear());
    }

    #[Test]
    public function getMultiple()
    {
        $this->assertSame(['foo' => 42, 'bar' => 42], NullCache::instance()->getMultiple(['foo', 'bar'], 42));
        $this->assertSame(['foo' => 42, 'bar' => 42], NullCache::instance()->getMultiple(new ArrayIterator(['foo', 'bar']), 42));
    }

    #[Test]
    public function setMultiple()
    {
        $cache = NullCache::instance();
        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux']));
        $this->assertSame(null, $cache->get('foo'));
        $this->assertSame(null, $cache->get('baz'));
    }

    #[Test]
    public function deleteMultiple()
    {
        $cache = NullCache::instance();
        $this->assertTrue($cache->deleteMultiple(['foo', 'bar']));
        $this->assertTrue($cache->deleteMultiple(new ArrayIterator(['foo', 'bar'])));
    }

    #[Test]
    public function has()
    {
        $cache = NullCache::instance();
        $this->assertFalse($cache->has('foo'));
    }

    #[Test]
    public function create()
    {
        $this->assertSame(NullCache::instance(), NullCache::create(new CacheConfig(), $this->createMock(ContainerInterface::class)));
    }
}
