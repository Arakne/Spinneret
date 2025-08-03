<?php

namespace Arakne\Tests\Spinneret\Container\Attribute;

use Arakne\Spinneret\Container\Attribute\Service;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    #[Test]
    public function emptyTag()
    {
        $builder = new ContainerBuilder();
        $builder->register(DefaultServiceAttribute::class);
        $container = $builder->build();

        $this->assertFalse($builder->services[DefaultServiceAttribute::class]->public);
        $this->assertTrue($builder->services[DefaultServiceAttribute::class]->shared);
        $this->assertSame([], $builder->services[DefaultServiceAttribute::class]->tags);
        $this->assertSame([], $builder->aliases);

        $this->assertInstanceOf(DefaultServiceAttribute::class, $container->get(DefaultServiceAttribute::class));
    }

    #[Test]
    public function public()
    {
        $builder = new ContainerBuilder();
        $builder->register(PublicService::class);
        $container = $builder->build();

        $this->assertTrue($builder->services[PublicService::class]->public);
        $this->assertTrue($builder->services[PublicService::class]->shared);
        $this->assertSame([], $builder->services[PublicService::class]->tags);
        $this->assertSame([], $builder->aliases);

        $this->assertInstanceOf(PublicService::class, $container->get(PublicService::class));
    }

    #[Test]
    public function tags()
    {
        $builder = new ContainerBuilder();
        $builder->register(TaggedService::class);
        $container = $builder->build();

        $this->assertFalse($builder->services[TaggedService::class]->public);
        $this->assertTrue($builder->services[TaggedService::class]->shared);
        $this->assertSame(['foo', 'bar'], $builder->services[TaggedService::class]->tags);
        $this->assertSame([], $builder->aliases);

        $this->assertInstanceOf(TaggedService::class, $container->get(TaggedService::class));
    }

    #[Test]
    public function aliases()
    {
        $builder = new ContainerBuilder();
        $builder->register(AliasedService::class);
        $container = $builder->build();

        $this->assertFalse($builder->services[AliasedService::class]->public);
        $this->assertTrue($builder->services[AliasedService::class]->shared);
        $this->assertSame([], $builder->services[AliasedService::class]->tags);
        $this->assertSame(['foo' => AliasedService::class, 'bar' => AliasedService::class], $builder->aliases);

        $this->assertInstanceOf(AliasedService::class, $container->get(AliasedService::class));
    }

    #[Test]
    public function useInterfacesAsAliases()
    {
        $builder = new ContainerBuilder();
        $builder->register(UseInterfacesAsAliasService::class);
        $container = $builder->build();

        $this->assertFalse($builder->services[UseInterfacesAsAliasService::class]->public);
        $this->assertTrue($builder->services[UseInterfacesAsAliasService::class]->shared);
        $this->assertSame([], $builder->services[UseInterfacesAsAliasService::class]->tags);
        $this->assertSame([FooInterface::class => UseInterfacesAsAliasService::class], $builder->aliases);

        $this->assertInstanceOf(UseInterfacesAsAliasService::class, $container->get(UseInterfacesAsAliasService::class));
    }
}

#[Service]
class DefaultServiceAttribute {}

#[Service(public: true)]
class PublicService {}

#[Service(tags: ['foo', 'bar'])]
class TaggedService {}

#[Service(aliases: ['foo', 'bar'])]
class AliasedService {}

interface FooInterface {}

#[Service(useInterfacesAsAliases: true)]
class UseInterfacesAsAliasService implements FooInterface {}
