<?php

namespace Arakne\Tests\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\Driver\MemoryCache;
use Arakne\Spinneret\Time\FixedClock;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class MemoryCacheTest extends TestCase
{
    #[Test]
    public function getSetWithoutTtl()
    {
        $cache = new MemoryCache();

        $this->assertFalse($cache->has('foo'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertSame(null, $cache->get('foo'));
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo'));

        $o = (object) ['baz' => 'qux'];
        $this->assertTrue($cache->set('obj', $o));
        $this->assertEquals($o, $cache->get('obj'));
        $this->assertNotSame($o, $cache->get('obj'));

        $this->assertTrue($cache->delete('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    #[Test]
    public function shouldHandleNullValue()
    {
        $cache = new MemoryCache();

        $this->assertFalse($cache->has('foo'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertNull($cache->get('foo'));

        $this->assertTrue($cache->set('foo', null));
        $this->assertTrue($cache->has('foo'));
        $this->assertSame(null, $cache->get('foo', 42));
        $this->assertNull($cache->get('foo'));
    }

    #[
        Test,
        TestWith([0]),
        TestWith([-1]),
        TestWith([new \DateInterval('PT0S')]),
        TestWith([new \DateInterval('PT2S')]),
    ]
    public function setWithNullOrNegativeTtlShouldDelete($ttl)
    {
        $cache = new MemoryCache();

        if ($ttl instanceof DateInterval) {
            $ttl->invert = 1;
        }

        $cache->set('foo', 'bar');
        $cache->set('foo', 'bar', $ttl);

        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    #[Test]
    public function setWithIntTtl()
    {
        $clock = new FixedClock(new DateTimeImmutable('2024-09-03T15:00:00'));
        $cache = new MemoryCache($clock);

        $cache->set('foo', 'bar', 3600);
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T15:30:00'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T16:00:01'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertFalse($cache->has('foo'));
    }

    #[Test]
    public function setWithDateIntervalTtl()
    {
        $clock = new FixedClock(new DateTimeImmutable('2024-09-03T15:00:00'));
        $cache = new MemoryCache($clock);

        $cache->set('foo', 'bar', new DateInterval('PT1H'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T15:30:00'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T16:00:01'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertFalse($cache->has('foo'));
    }

    #[Test]
    public function getMultipleWithoutTtl()
    {
        $cache = new MemoryCache();

        $this->assertSame(['foo' => 42, 'bar' => 42, 'baz' => 42], iterator_to_array($cache->getMultiple(['foo', 'bar', 'baz'], 42)));

        $cache->set('foo', 'baz');
        $o = (object) ['qux' => 'quux'];
        $cache->set('bar', $o);

        $this->assertEquals(['foo' => 'baz', 'bar' => $o, 'baz' => 42], iterator_to_array($cache->getMultiple(['foo', 'bar', 'baz'], 42)));
        $this->assertNotSame($o, iterator_to_array($cache->getMultiple(['bar'], 42))['bar']);
    }

    #[Test]
    public function getMultipleWithTtl()
    {
        $clock = new FixedClock(new DateTimeImmutable('2024-09-03T15:00:00'));
        $cache = new MemoryCache($clock);

        $cache->set('foo', 'bar', 3600);
        $cache->set('bar', 'baz', 7200);

        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], iterator_to_array($cache->getMultiple(['foo', 'bar'])));

        $clock->setNow(new DateTimeImmutable('2024-09-03T16:00:01'));
        $this->assertSame(['foo' => 42, 'bar' => 'baz'], iterator_to_array($cache->getMultiple(['foo', 'bar'], 42)));

        $clock->setNow(new DateTimeImmutable('2024-09-03T17:00:01'));
        $this->assertSame(['foo' => 42, 'bar' => 42], iterator_to_array($cache->getMultiple(['foo', 'bar'], 42)));
    }

    #[Test]
    public function setMultipleWithoutTtl()
    {
        $cache = new MemoryCache();

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux']));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame('qux', $cache->get('baz'));
    }

    #[Test]
    public function setMultipleWithTtl()
    {
        $clock = new FixedClock(new DateTimeImmutable('2024-09-03T15:00:00'));
        $cache = new MemoryCache($clock);

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux'], 3600));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame('qux', $cache->get('baz'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T15:30:00'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame('qux', $cache->get('baz'));

        $clock->setNow(new DateTimeImmutable('2024-09-03T16:00:01'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertSame(42, $cache->get('baz', 42));
    }

    #[Test]
    public function setMultipleWithNegativeTtlShouldDeleteAll()
    {
        $cache = new MemoryCache();

        $cache->set('foo', 'bar');
        $cache->set('baz', 'qux');

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux'], -1));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('baz'));
    }

    #[Test]
    public function setMultipleShouldCloneObject()
    {
        $cache = new MemoryCache();

        $o = (object) ['foo' => 'bar'];
        $this->assertTrue($cache->setMultiple(['obj' => $o]));

        $o->foo = 'baz';

        $this->assertEquals((object) ['foo' => 'bar'], $cache->get('obj'));
    }

    #[Test]
    public function deleteMultiple()
    {
        $cache = new MemoryCache();

        $cache->setMultiple(['foo' => 'bar', 'baz' => 'qux', 'quux' => 'corge']);

        $this->assertTrue($cache->deleteMultiple(['foo', 'baz']));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('baz'));
        $this->assertTrue($cache->has('quux'));
    }
}
