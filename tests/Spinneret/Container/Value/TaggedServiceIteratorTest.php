<?php

namespace Arakne\Tests\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Value\TaggedServiceIterator;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\MyTagInterface;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TagContainer;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TaggedA;
use Arakne\Tests\Spinneret\Container\Fixtures\Tagged\TaggedB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

use function iterator_to_array;

class TaggedServiceIteratorTest extends TestCase
{
    #[Test]
    public function resolveFunctional()
    {
        $builder = new ContainerBuilder();
        $builder->register(TagContainer::class)->arg(new TaggedServiceIterator(MyTagInterface::class));
        $builder->register(TaggedA::class)->tag(MyTagInterface::class);
        $builder->register(TaggedB::class)->tag(MyTagInterface::class);

        $container = $builder->build();

        $tagContainer = $container->get(TagContainer::class);
        $this->assertInstanceOf(TagContainer::class, $tagContainer);
        $this->assertCount(2, $tagContainer->tagged);
        $this->assertInstanceOf(TaggedA::class, $tagContainer->tagged[0]);
        $this->assertInstanceOf(TaggedB::class, $tagContainer->tagged[1]);
    }

    #[Test]
    public function resolve()
    {
        $builder = new ContainerBuilder();
        $builder->register(TaggedA::class)->tag(MyTagInterface::class);
        $builder->register(TaggedB::class)->tag(MyTagInterface::class);

        $container = $builder->build();

        $resolved = iterator_to_array(new TaggedServiceIterator(MyTagInterface::class)->resolve($container));
        $this->assertCount(2, $resolved);
        $this->assertInstanceOf(TaggedA::class, $resolved[0]);
        $this->assertInstanceOf(TaggedB::class, $resolved[1]);
    }

    #[Test]
    public function resolveInvalidContainerInstance()
    {
        $this->expectException(ContainerBuildException::class);
        $this->expectExceptionMessage('Container does not support tagged services.');

        new TaggedServiceIterator(MyTagInterface::class)->resolve($this->createMock(ContainerInterface::class));
    }

    #[Test]
    public function compile()
    {
        $this->assertSame('$this->findByTag(\'Arakne\\\Tests\\\Spinneret\\\Container\\\Fixtures\\\Tagged\\\MyTagInterface\')', (new TaggedServiceIterator(MyTagInterface::class))->compile());
    }

    #[Test]
    public function type()
    {
        $this->assertNull(new TaggedServiceIterator(MyTagInterface::class)->type());
    }
}
