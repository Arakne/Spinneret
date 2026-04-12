<?php

namespace Arakne\Tests\Spinneret\Cache\Driver;

use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Cache\Driver\ApcuCache;
use Arakne\Spinneret\Cache\Exception\InvalidKeyException;
use DateInterval;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function apcu_clear_cache;
use function iterator_to_array;

#[RequiresPhpExtension('apcu')]
class ApcuCacheTest extends TestCase
{
    protected function setUp(): void
    {
        if (!filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('APCu is not enabled for CLI.');
        }

        apcu_clear_cache();
    }

    #[Test]
    public function getSetWithoutTtl()
    {
        $cache = new ApcuCache();

        $this->assertFalse($cache->has('foo'));
        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertSame(null, $cache->get('foo'));
        $this->assertTrue($cache->set('foo', 'bar'));
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo'));

        $this->assertTrue($cache->set('int_val', 123));
        $this->assertSame(123, $cache->get('int_val'));

        $this->assertTrue($cache->delete('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    #[Test]
    public function shouldHandleNullValue()
    {
        $cache = new ApcuCache();

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
    ]
    public function setWithNullOrNegativeTtlShouldDelete(int|DateInterval $ttl)
    {
        $cache = new ApcuCache();

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
        $cache = new ApcuCache();

        $cache->set('foo', 'bar', 3600);
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));
    }

    #[Test]
    public function setWithIntTtlShouldExpire()
    {
        $cache = new ApcuCache();

        $cache->set('foo', 'bar', 1);
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        sleep(2);

        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertFalse($cache->has('foo'));
    }

    #[Test]
    public function setWithDateIntervalTtl()
    {
        $cache = new ApcuCache();

        $cache->set('foo', 'bar', new DateInterval('PT1H'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));
    }

    #[Test]
    public function setWithDateIntervalTtlShouldExpire()
    {
        $cache = new ApcuCache();

        $cache->set('foo', 'bar', new DateInterval('PT1S'));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertTrue($cache->has('foo'));

        sleep(2);

        $this->assertSame(42, $cache->get('foo', 42));
        $this->assertFalse($cache->has('foo'));
    }

    #[Test]
    public function getMultiple()
    {
        $cache = new ApcuCache();

        $this->assertSame(['foo' => 42, 'bar' => 42, 'baz' => 42], iterator_to_array($cache->getMultiple(['foo', 'bar', 'baz'], 42)));

        $cache->set('foo', 'value1');
        $cache->set('bar', 'value2');

        $this->assertSame(['foo' => 'value1', 'bar' => 'value2', 'baz' => 42], iterator_to_array($cache->getMultiple(['foo', 'bar', 'baz'], 42)));
    }

    #[Test]
    public function setMultipleWithoutTtl()
    {
        $cache = new ApcuCache();

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux']));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame('qux', $cache->get('baz'));
    }

    #[Test]
    public function setMultipleWithTtl()
    {
        $cache = new ApcuCache();

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux'], 3600));
        $this->assertSame('bar', $cache->get('foo'));
        $this->assertSame('qux', $cache->get('baz'));
    }

    #[Test]
    public function setMultipleWithNegativeTtlShouldDeleteAll()
    {
        $cache = new ApcuCache();

        $cache->set('foo', 'bar');
        $cache->set('baz', 'qux');

        $this->assertTrue($cache->setMultiple(['foo' => 'bar', 'baz' => 'qux'], -1));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('baz'));
    }

    #[Test]
    public function deleteMultiple()
    {
        $cache = new ApcuCache();

        $cache->setMultiple(['foo' => 'bar', 'baz' => 'qux', 'quux' => 'corge']);

        $this->assertTrue($cache->deleteMultiple(['foo', 'baz']));
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('baz'));
        $this->assertTrue($cache->has('quux'));
    }

    #[Test]
    public function clearWithoutPrefix()
    {
        $cache = new ApcuCache();

        $cache->set('foo', 'bar');
        $cache->set('baz', 'qux');

        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('baz'));
    }

    #[Test]
    public function clearWithPrefixShouldOnlyRemovePrefixedEntries()
    {
        $cache1 = new ApcuCache('prefix1_');
        $cache2 = new ApcuCache('prefix2_');

        $cache1->set('foo', 'bar');
        $cache2->set('foo', 'baz');

        $cache1->clear();

        $this->assertFalse($cache1->has('foo'));
        $this->assertTrue($cache2->has('foo'));
        $this->assertSame('baz', $cache2->get('foo'));
    }

    #[Test]
    public function prefixShouldIsolateKeys()
    {
        $cache1 = new ApcuCache('ns1_');
        $cache2 = new ApcuCache('ns2_');

        $cache1->set('foo', 'bar');
        $cache2->set('foo', 'baz');

        $this->assertSame('bar', $cache1->get('foo'));
        $this->assertSame('baz', $cache2->get('foo'));

        $cache1->delete('foo');
        $this->assertFalse($cache1->has('foo'));
        $this->assertTrue($cache2->has('foo'));
    }

    #[Test]
    public function prefixWithGetMultiple()
    {
        $cache = new ApcuCache('pfx_');

        $cache->set('a', 1);
        $cache->set('b', 2);

        $result = iterator_to_array($cache->getMultiple(['a', 'b', 'c'], 'default'));

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 'default'], $result);
    }

    #[Test]
    public function prefixWithSetMultiple()
    {
        $cache1 = new ApcuCache('ns1_');
        $cache2 = new ApcuCache('ns2_');

        $cache1->setMultiple(['foo' => 'bar', 'baz' => 'qux']);
        $cache2->setMultiple(['foo' => 'other']);

        $this->assertSame('bar', $cache1->get('foo'));
        $this->assertSame('other', $cache2->get('foo'));
        $this->assertSame('qux', $cache1->get('baz'));
        $this->assertNull($cache2->get('baz'));
    }

    #[Test]
    public function prefixWithDeleteMultiple()
    {
        $cache1 = new ApcuCache('ns1_');
        $cache2 = new ApcuCache('ns2_');

        $cache1->set('foo', 'bar');
        $cache2->set('foo', 'baz');

        $cache1->deleteMultiple(['foo']);

        $this->assertFalse($cache1->has('foo'));
        $this->assertTrue($cache2->has('foo'));
    }

    #[Test]
    public function getWithInvalidKeyShouldThrow()
    {
        $cache = new ApcuCache();

        $this->expectException(InvalidKeyException::class);
        $cache->get('foo{bar');
    }

    #[Test]
    public function setWithInvalidKeyShouldThrow()
    {
        $cache = new ApcuCache();

        $this->expectException(InvalidKeyException::class);
        $cache->set('foo/bar', 'value');
    }

    #[Test]
    public function deleteWithInvalidKeyShouldThrow()
    {
        $cache = new ApcuCache();

        $this->expectException(InvalidKeyException::class);
        $cache->delete('foo@bar');
    }

    #[Test]
    public function hasWithInvalidKeyShouldThrow()
    {
        $cache = new ApcuCache();

        $this->expectException(InvalidKeyException::class);
        $cache->has('foo:bar');
    }

    #[Test]
    public function shouldStoreAndRetrieveObjects()
    {
        $cache = new ApcuCache();

        $o = (object) ['baz' => 'qux'];
        $this->assertTrue($cache->set('obj', $o));
        $this->assertEquals($o, $cache->get('obj'));
        $this->assertNotSame($o, $cache->get('obj'));
    }

    #[Test]
    public function shouldStoreAndRetrieveArrays()
    {
        $cache = new ApcuCache();

        $arr = ['foo' => 'bar', 'nested' => [1, 2, 3]];
        $this->assertTrue($cache->set('arr', $arr));
        $this->assertSame($arr, $cache->get('arr'));
    }

    #[Test]
    public function create()
    {
        $config = new CacheConfig(namespace: 'test_');
        $container = $this->createMock(ContainerInterface::class);

        $cache = ApcuCache::create($config, $container);

        $this->assertInstanceOf(ApcuCache::class, $cache);

        $cache->set('foo', 'bar');
        $this->assertSame('bar', $cache->get('foo'));
    }
}
