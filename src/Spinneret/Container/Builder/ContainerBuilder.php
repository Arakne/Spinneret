<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Builder\Processor\AutowireProcessor;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Builder\Processor\InlineTaggedIteratorProcessor;
use Arakne\Spinneret\Container\Builder\Processor\ResolveAliasesProcessor;
use Arakne\Spinneret\Container\BuiltContainer;
use Generator;

use function array_map;

/**
 * Builder for a container.
 * Define services and aliases, and build the container.
 *
 * Usage:
 * ```php
 * $builder = new ContainerBuilder();
 *
 * // Register a service.
 * // If no parameters are provided, it will be autowired.
 * $builder->register(MyService::class);
 *
 * // You can explicitly define arguments for the service.
 * $builder->register(MyOtherService::class)
 *     ->arg(new Reference(Foo::class))
 *     ->arg(42)
 * ;
 *
 * // Service ID can also be a custom string.
 * // In this case, you must provide a class name.
 * $builder->register('foo')->class(MyClass::class)->arg('foo');
 * $builder->register('bar')->class(MyClass::class)->arg('bar');
 *
 * // You can use a factory to create the service.
 * // Like with constructors, the factory can have parameters.
 * // First class callable syntax can be used if the method is public.
 * $builder->register(MyComplexService::class)->factory(MyServiceFactory::create(...));
 *
 * // To call a method on a service to create another service, simply use array syntax.
 * // The first element is the reference, the second is the method name.
 * $builder->register(MyComplexService::class)->factory([new Reference(MyServiceFactory::class), 'create']);
 *
 * // You can add tags to services.
 * $builder->register(FooHandler::class)->tag(HandlerTag::class);
 * $builder->register(BarHandler::class)->tag(HandlerTag::class);
 *
 * // To add a tag with parameters, use an instance of the tag class.
 * $builder->register(BazHandler::class)->tag(new HandlerTag(priority: 10, async: true));
 *
 * foreach ($builder->findByTag(HandlerTag::class) as $service => $tags) {
 *     // $service is a ServiceBuilder instance.
 *     // $tags is a list of tags instances (here instances of HandlerTag).
 *     // If the tag is a string, it will be ignored.
 * }
 *
 * // Aliases can be defined to refer to a service ID or another alias.
 * $builder->alias('my_alias', MyService::class);
 * $builder->alias('my_other_alias', 'my_alias');
 *
 * // You can also register a processor to modify the container at build time.
 * // This is useful for custom processing of services, processing tags, etc.
 * $builder->processor(new class implements ContainerBuilderProcessorInterface {
 *     public function process(ContainerBuilder $builder): void
 *     {
 *         foreach ($builder->services as $service) {
 *             // ...
 *         }
 *     }
 * });
 * ```
 */
final class ContainerBuilder
{
    /**
     * @var array<string, ServiceBuilder>
     */
    public private(set) array $services = [];

    /**
     * @var array<string, string>
     */
    public private(set) array $aliases = [];

    /**
     * @var list<ContainerBuilderProcessorInterface>
     */
    private array $processors;

    public function __construct()
    {
        $this->processors = [
            new AutowireProcessor(),
            new InlineTaggedIteratorProcessor(),
            new ResolveAliasesProcessor(),
        ];
    }

    /**
     * Check if the given service ID is defined in the container.
     * It can be a service ID or an alias.
     *
     * @param string $id ID to check.
     *
     * @return bool true if the service or alias is defined, false otherwise.
     */
    public function defined(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->aliases[$id]);
    }

    // @todo anonymous services
    // @todo defined arguments as 2nd parameter of register()
    public function register(string $id): ServiceBuilder
    {
        /** @psalm-suppress ArgumentTypeCoercion - TODO: null if class doesn't exists */
        return $this->services[$id] = new ServiceBuilder($id, $id);
    }

    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    public function processor(ContainerBuilderProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * @param string|class-string<T> $tag
     * @return Generator<ServiceBuilder, list<T>>
     *
     * @template T
     */
    public function findByTag(string $tag): Generator
    {
        foreach ($this->services as $service) {
            $tags = [];
            $match = false;

            foreach ($service->tags as $serviceTag) {
                if ($serviceTag === $tag) {
                    $match = true;
                    continue;
                }

                if ($serviceTag instanceof $tag) {
                    $match = true;
                    $tags[] = $serviceTag;
                }
            }

            if ($match) {
                yield $service => $tags;
            }
        }
    }

    /**
     * Build the container.
     *
     * Processors will be applied and autowiring will be resolved.
     * The result container cannot be modified.
     *
     * @return BuiltContainer
     */
    public function build(): BuiltContainer
    {
        foreach ($this->processors as $processor) {
            $processor->process($this);
        }

        return new BuiltContainer(
            services: array_map(static fn (ServiceBuilder $service) => $service->build(), $this->services),
            aliases: $this->aliases,
        );
    }
}
