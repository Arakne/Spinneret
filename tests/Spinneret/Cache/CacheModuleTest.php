<?php

namespace Arakne\Tests\Spinneret\Cache;

use Arakne\Spinneret\Cache\CacheConfig;
use Arakne\Spinneret\Cache\CacheFetcher;
use Arakne\Spinneret\Cache\CacheModule;
use Arakne\Spinneret\Cache\Driver\ApcuCache;
use Arakne\Spinneret\Cache\Driver\CacheDriverInterface;
use Arakne\Spinneret\Cache\Driver\MemoryCache;
use Arakne\Spinneret\Cache\Driver\NullCache;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Tests\Spinneret\Cache\Fixtures\MarkerInterface;
use Arakne\Tests\Spinneret\Cache\Fixtures\ServiceA;
use Arakne\Tests\Spinneret\Cache\Fixtures\ServiceB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheModuleTest extends TestCase
{
    #[Test]
    public function defaultConfig()
    {
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set(new CacheConfig());
        new CacheModule()->register($container);

        $built = $container->build();

        $this->assertInstanceOf(NullCache::class, $built->get(CacheInterface::class));
        $this->assertInstanceOf(NullCache::class, $built->get(CacheDriverInterface::class));
        $this->assertInstanceOf(CacheFetcher::class, $built->get(CacheFetcher::class));
    }

    #[Test]
    public function withCustomConfig()
    {
        $module = new CacheModule();
        $config = new CacheConfig(
            driver: MemoryCache::class,
        );

        $module = $module->withConfiguration($config);
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set($config);
        $module->register($container);

        $container->import(__DIR__ . '/Fixtures', __NAMESPACE__ . '\\Fixtures');

        $built = $container->build();

        $this->assertInstanceOf(MemoryCache::class, $built->get(CacheInterface::class));
        $this->assertInstanceOf(MemoryCache::class, $built->get(CacheDriverInterface::class));
        $this->assertInstanceOf(CacheFetcher::class, $built->get(CacheFetcher::class));

        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceA::class)->cache);
        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceB::class)->cache);
        $this->assertEquals(new CacheFetcher(new MemoryCache()), $built->get(ServiceB::class)->fetcher);
    }

    #[Test]
    public function withOverrideExactServiceId()
    {
        $module = new CacheModule();
        $config = new CacheConfig(
            driver: ApcuCache::class,
            namespace: 'test.',
            overrides: [
                ServiceA::class => new CacheConfig(driver: MemoryCache::class),
            ],
        );

        $module = $module->withConfiguration($config);
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set($config);
        $module->register($container);

        $container->import(__DIR__ . '/Fixtures', __NAMESPACE__ . '\\Fixtures');

        $built = $container->build();

        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheInterface::class));
        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheDriverInterface::class));
        $this->assertEquals(new ApcuCache('test.'), $built->get(CacheInterface::class));

        $this->assertInstanceOf(MemoryCache::class, $built->get(ServiceA::class)->cache);
        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceB::class)->cache);
        $this->assertEquals(new CacheFetcher($built->get(CacheInterface::class)), $built->get(ServiceB::class)->fetcher);
    }

    #[Test]
    public function withOverrideInterface()
    {
        $module = new CacheModule();
        $config = new CacheConfig(
            driver: ApcuCache::class,
            namespace: 'test.',
            overrides: [
                MarkerInterface::class => new CacheConfig(driver: MemoryCache::class),
            ],
        );

        $module = $module->withConfiguration($config);
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set($config);
        $module->register($container);

        $container->import(__DIR__ . '/Fixtures', __NAMESPACE__ . '\\Fixtures');

        $built = $container->build();

        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheInterface::class));
        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheDriverInterface::class));
        $this->assertEquals(new ApcuCache('test.'), $built->get(CacheInterface::class));

        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceA::class)->cache);
        $this->assertInstanceOf(MemoryCache::class, $built->get(ServiceB::class)->cache);
        $this->assertEquals(new CacheFetcher(new MemoryCache()), $built->get(ServiceB::class)->fetcher);
    }

    #[Test]
    public function configIsResolveAtRuntime()
    {
        $module = new CacheModule();
        $config = new CacheConfig();

        $module = $module->withConfiguration($config);
        $container = new ContainerBuilder(registerAsPublic: true);
        $container->set($config);
        $module->register($container);

        $container->import(__DIR__ . '/Fixtures', __NAMESPACE__ . '\\Fixtures');

        $built = $container->build();
        $built->set(CacheConfig::class, new CacheConfig(
            driver: ApcuCache::class,
            namespace: 'test.',
        ));

        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheInterface::class));
        $this->assertInstanceOf(ApcuCache::class, $built->get(CacheDriverInterface::class));
        $this->assertEquals(new ApcuCache('test.'), $built->get(CacheDriverInterface::class));

        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceA::class)->cache);
        $this->assertSame($built->get(CacheInterface::class), $built->get(ServiceB::class)->cache);
    }
}
