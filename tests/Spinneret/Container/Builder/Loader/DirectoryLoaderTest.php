<?php

namespace Arakne\Tests\Spinneret\Container\Builder\Loader;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\Loader\DirectoryLoader;
use Arakne\Tests\Spinneret\Container\Builder\Loader\Fixtures\Simple\A;
use Arakne\Tests\Spinneret\Container\Builder\Loader\Fixtures\Simple\Dir\B;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_contains;

class DirectoryLoaderTest extends TestCase
{
    #[Test]
    public function loadSuccess()
    {
        $builder = new ContainerBuilder();
        $loader = new DirectoryLoader(__DIR__.'/Fixtures/Simple', __NAMESPACE__.'\Fixtures\Simple');
        $loader->load($builder);

        $this->assertArrayHasKey(A::class, $builder->services);
        $this->assertArrayHasKey(B::class, $builder->services);

        $this->assertTrue($builder->services[A::class]->ignoreIfInvalid);
        $this->assertSame(A::class, $builder->services[A::class]->class);
        $this->assertTrue($builder->services[B::class]->ignoreIfInvalid);
        $this->assertSame(B::class, $builder->services[B::class]->class);
    }

    #[Test]
    public function loadWithFilter()
    {
        $builder = new ContainerBuilder();
        $loader = new DirectoryLoader(
            __DIR__.'/Fixtures/Simple',
            __NAMESPACE__.'\Fixtures\Simple',
            static fn (string $class): bool => !str_contains($class, 'Dir')
        );
        $loader->load($builder);

        $this->assertArrayHasKey(A::class, $builder->services);
        $this->assertArrayNotHasKey(B::class, $builder->services);

        $this->assertTrue($builder->services[A::class]->ignoreIfInvalid);
        $this->assertSame(A::class, $builder->services[A::class]->class);
    }

    #[Test]
    public function shouldIgnoreFileThatNotContainsClass()
    {
        $builder = new ContainerBuilder();
        $loader = new DirectoryLoader(__DIR__.'/Fixtures/NotPhpClasses', __NAMESPACE__.'\Fixtures\NotPhpClasses');
        $loader->load($builder);

        $this->assertCount(0, $builder->services);
    }

    #[Test]
    public function shouldSkipAlreadyLoadedClass()
    {
        $builder = new ContainerBuilder();
        $builder->register(A::class);
        $builder->register(B::class);

        $loader = new DirectoryLoader(__DIR__.'/Fixtures/Simple', __NAMESPACE__.'\Fixtures\Simple');
        $loader->load($builder);

        $this->assertFalse($builder->services[A::class]->ignoreIfInvalid);
        $this->assertFalse($builder->services[B::class]->ignoreIfInvalid);
    }

    #[Test]
    public function shouldIgnoreIfNotFollowPsr4()
    {
        $builder = new ContainerBuilder();
        $loader = new DirectoryLoader(__DIR__.'/Fixtures/NotPsr4', __NAMESPACE__.'\Fixtures\NotPsr4');
        $loader->load($builder);

        $this->assertCount(0, $builder->services);
    }
}
