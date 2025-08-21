<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Builder\Configurator\AttributeConfigurator;
use Arakne\Spinneret\Container\Builder\Configurator\ConfiguratorInterface;
use Arakne\Spinneret\Container\Builder\Configurator\InstanceOfConfigurator;
use Arakne\Spinneret\Container\Builder\Configurator\ServiceConfiguratorAttributeConfigurator;
use Arakne\Spinneret\Container\Builder\Loader\DirectoryLoader;
use Arakne\Spinneret\Container\Builder\Processor\AutowireProcessor;
use Arakne\Spinneret\Container\Builder\Processor\ContainerBuilderProcessorInterface;
use Arakne\Spinneret\Container\Builder\Processor\InlineInvalidReferenceFallbackProcessor;
use Arakne\Spinneret\Container\Builder\Processor\InlineServicesProcessor;
use Arakne\Spinneret\Container\Builder\Processor\InlineTaggedIteratorProcessor;
use Arakne\Spinneret\Container\Builder\Processor\RemoveInvalidServicesProcessor;
use Arakne\Spinneret\Container\Builder\Processor\RemoveUnusedServicesProcessor;
use Arakne\Spinneret\Container\Builder\Processor\ResolveAliasesProcessor;
use Arakne\Spinneret\Container\BuiltContainer;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Closure;
use Throwable;

use function class_exists;
use function count;
use function is_object;
use function sprintf;

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
 * // You can also directly use a value to register a service.
 * $builder->register(MyValueService::class)->value(new MyValueService('foo', 42));
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
     * @var list<ConfiguratorInterface>
     */
    private array $configurators;

    /**
     * @var array<ContainerBuilderProcessorInterface::STEP_*, list<ContainerBuilderProcessorInterface>>
     */
    private array $processors;

    public function __construct(
        /**
         * If true, all services will be registered as public by default.
         *
         * Note: enabling this flag will disable almost all optimizations,
         * so use it only in development or for debugging purposes.
         */
        public readonly bool $registerAsPublic = false,
    ) {
        $this->configurators = [
            new ServiceConfiguratorAttributeConfigurator(),
        ];

        $this->processors = [
            ContainerBuilderProcessorInterface::STEP_PROCESS => [
                new AutowireProcessor(),
            ],
            ContainerBuilderProcessorInterface::STEP_FINALIZE => [
                new AutowireProcessor(),
                new InlineTaggedIteratorProcessor(),
                new ResolveAliasesProcessor(),
                new RemoveInvalidServicesProcessor(),
                new InlineServicesProcessor(),
                new InlineInvalidReferenceFallbackProcessor(),
                new RemoveUnusedServicesProcessor(),
            ],
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

    /**
     * Find a service by its ID or alias.
     *
     * @param string $id The service ID or alias to find.
     * @return ServiceBuilder|null The service, or null if not found.
     */
    public function find(string $id): ?ServiceBuilder
    {
        while (($alias = $this->aliases[$id] ?? null) !== null) {
            $id = $alias;
        }

        return $this->services[$id] ?? null;
    }

    /**
     * Remove the service or alias with the given ID from the container.
     *
     * Note: this will not remove aliases that point to this service, so calling this method may lead to broken aliases.
     *
     * @param string $id The service ID or alias to remove.
     */
    public function remove(string $id): void
    {
        unset($this->services[$id]);
        unset($this->aliases[$id]);
    }

    /**
     * Register a service in the container.
     *
     * Usage:
     * ```php
     * $builder->register(MyService::class); // Autowired service
     * $builder->register(MyService::class, [new Reference('dependency'), 42]); // With arguments
     * $builder->register(MyService::class)
     *     ->arg(new Reference('dependency')) // With arguments, using builder methods
     *     ->arg(42)
     * ;
     * $builder->register('my_service_id', [new Reference('dependency'), 42])->class(MyService::class); // With service ID
     * ```
     *
     * @param string $id The service ID. If it's a class name, it will be used as the service class.
     * @param list<mixed>|null $arguments Arguments to pass to the service constructor or factory.
     *
     * @return ServiceBuilder
     */
    public function register(string $id, ?array $arguments = null): ServiceBuilder
    {
        $builder = $this->services[$id] = new ServiceBuilder($id, class_exists($id) ? $id : null);
        $builder->public = $this->registerAsPublic;

        if ($arguments !== null) {
            $builder->arguments = $arguments;
        }

        return $builder;
    }

    /**
     * Find a service by its ID or alias, or register it if it does not exist.
     *
     * Usage:
     * ```php
     * // Add the tag MyTag to the service MyService, and register it if it does not exist.
     * // Assume that MyService can be autowired.
     * $builder->findOrRegister(MyService::class)->tag(MyTag::class);
     *
     * // You can configure the service if it's not registered, so you do not depend on autowiring.
     * $builder->findOrRegister(OtherService::class, function (ServiceBuilder $service) {
     *     $service->arg(new Reference(MyService::class));
     * })->tag(MyTag::class);
     * ```
     *
     * @param string $id The service ID. If it's a class name, it will be used as the service class.
     * @param (Closure(ServiceBuilder):void)|null $configurator The service configurator to apply if the service is registered.
     *
     * @return ServiceBuilder
     */
    public function findOrRegister(string $id, ?Closure $configurator = null): ServiceBuilder
    {
        if ($service = $this->find($id)) {
            return $service;
        }

        $service = $this->register($id);

        if ($configurator) {
            $configurator($service);
        }

        return $service;
    }

    /**
     * Define a value service.
     *
     * This method is equivalent to calling `$builder->register($id)->value($value)`
     * or `$builder->register($value::class)->value($value)` if the first parameter is an object.
     *
     * @param string|object $id The service ID, or value to register if you want to use the class name as ID.
     * @param mixed|null $value The value to register as a service.
     *
     * @return ServiceBuilder
     */
    public function set(string|object $id, mixed $value = null): ServiceBuilder
    {
        if (is_object($id)) {
            $value = $id;
            $id = $value::class;
        }

        return $this->register($id)->value($value);
    }

    /**
     * Register an anonymous service.
     *
     * An anonymous service is a service that does not have a specific ID,
     * so it cannot be referenced directly.
     * Those services should be referenced using a tag.
     *
     * Usage:
     * ```php
     * $builder->anonymous(MyService::class)->tag(MyTag::class);
     * $builder->anonymous(OtherService::class)->tag(MyTag::class);
     * $builder->register(TagContainer::class, [new TaggedServiceIterator(MyTag::class)]);
     * ```
     *
     * @param class-string|null $class The class name of the service.
     * @param list<mixed>|null $arguments Arguments to pass to the service constructor or factory.
     *
     * @return ServiceBuilder
     */
    public function anonymous(?string $class = null, ?array $arguments = null): ServiceBuilder
    {
        $id = sprintf('__anonymous_%d__', count($this->services));

        $builder = $this->services[$id] = new ServiceBuilder($id, $class);

        if ($arguments !== null) {
            $builder->arguments = $arguments;
        }

        return $builder;
    }

    /**
     * Push an anonymous value into the container.
     * This method is equivalent to calling `$builder->anonymous()->value($value)`.
     *
     * Use configurator or tags to reference this value later.
     *
     * Usage:
     * ```php
     * $builder->configureInstanceOf(MyValue::class, function (ServiceBuilder $service) {
     *     $service->tag(MyValue::class);
     * });
     *
     * $builder->register(MyContainer::class, [new TaggedServiceIterator(MyValue::class)]);
     * $builder->push(new MyValue('foo', 42));
     * $builder->push(new MyValue('bar', 84));
     * $builder->push(new MyValue('baz', 32));
     * ```
     *
     * @param mixed $value
     * @return ServiceBuilder
     */
    public function push(mixed $value): ServiceBuilder
    {
        return $this->anonymous(is_object($value) ? $value::class : null)->value($value);
    }

    /**
     * Import all classes from a directory into the container.
     *
     * Usage:
     * ```php
     * $builder->import(__DIR__, __NAMESPACE__); // Import all classes in the current directory
     * ```
     *
     * @param string $directory The directory to scan for classes.
     * @param string $namespace The namespace to use for the classes found in the directory. All classes should follow PSR-4 standards.
     *
     * @return void
     */
    public function import(string $directory, string $namespace = ''): void
    {
        new DirectoryLoader($directory, $namespace)->load($this);
    }

    /**
     * Define an alias for a service ID.
     *
     * Usage:
     * ```php
     * $builder->alias('my_alias', MyService::class); // Alias for a service ID
     * $builder->alias('my_other_alias', 'my_alias'); // Alias for another alias
     *
     * $container = $builder->build();
     * // $container->get('my_alias'); // Will return the service registered as MyService::class
     * ```
     *
     * @param string $alias The alias name.
     * @param string $id The target service ID or another alias.
     *
     * @return void
     */
    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * Register a processor to modify the container at build time.
     *
     * @todo closure processors
     *
     * @param ContainerBuilderProcessorInterface $processor
     * @param ContainerBuilderProcessorInterface::STEP_* $step The step at which the processor should be applied.
     * @return void
     */
    public function processor(ContainerBuilderProcessorInterface $processor, int $step = ContainerBuilderProcessorInterface::STEP_PROCESS): void
    {
        $this->processors[$step][] = $processor;
    }

    /**
     * Register a configurator to apply to services before building the container.
     *
     * @param ConfiguratorInterface $configurator
     * @return void
     */
    public function configurator(ConfiguratorInterface $configurator): void
    {
        $this->configurators[] = $configurator;
    }

    /**
     * Apply the given configurator to services that are instances of a specific class or interface.
     *
     * Configurator will be applied to all services that match the type. The registration order does not matter.
     * There is no need to register the configurator before the service.
     *
     * Usage:
     * ```php
     * $builder->configureInstanceOf(ControllerInterface::class, function (ServiceBuilder $service) {
     *     $service
     *         ->public(true)
     *         ->tag(ControllerInterface::class)
     *     ;
     * });
     *
     * // Configurator will be applied on MyController, so it will be public and tagged as ControllerInterface.
     * $builder->register(MyController::class);
     * ```
     *
     * @param class-string $type The class or interface type to match.
     * @param Closure(ServiceBuilder, ContainerBuilder):void $configurator The configurator to apply to matching services.
     *
     * @return void
     */
    public function configureInstanceOf(string $type, Closure $configurator): void
    {
        $this->configurator(new InstanceOfConfigurator($type, $configurator));
    }

    /**
     * Apply the given configurator to services that have a specific attribute at class level.
     *
     * Configurator will be applied to all services has the given attribute. The registration order does not matter.
     * There is no need to register the configurator before the service.
     *
     * Usage:
     * ```php
     * #[Attribute(Attribute::TARGET_CLASS)]
     * class EventListener
     * {
     *     public function __construct(
     *         public string $event,
     *     ) {}
     * }
     *
     * #[EventListener('my_event')]
     * class MyEventListener
     * {
     *     // ...
     * }
     *
     * $builder->configureAttribute(EventListener::class, function (ServiceBuilder $service, EventListener $attribute) {
     *     $service->tag($attribute);
     * });
     *
     * // The configurator will be applied on MyEventListener, so it will be tagged with the EventListener attribute.
     * $builder->register(MyEventListener::class);
     * ```
     *
     * @param class-string<T> $type
     * @param Closure(ServiceBuilder, ContainerBuilder, T):void $configurator
     *
     * @return void
     *
     * @template T as object
     */
    public function configureAttribute(string $type, Closure $configurator): void
    {
        $this->configurator(new AttributeConfigurator($type, $configurator));
    }

    /**
     * Find services that have a specific tag.
     *
     * It will return an iterator, with the service build as the key,
     * and a list of tags instances as the value.
     *
     * If the tag is a string, the service will be returned but the tag will be ignored.
     *
     * Usage:
     * ```php
     * $values = new SplPriorityQueue();
     *
     * foreach ($builder->findByTag(MyTag::class) as $service => $tags) {
     *     // You can modify the service
     *     $service->arg(new Reference('some_dependency'));
     *
     *     // Or use tags
     *     foreach ($tags as $tag) {
     *         $values->insert($service, $tag->priority);
     *     }
     * }
     *
     * // And use it as resolver
     * $builder->services[MyService::class]->arguments[0] = iterator_to_array($values);
     * ```
     *
     * @param string|class-string<T> $tag The tag to search for.
     * @return iterable<ServiceBuilder, list<T>>
     *
     * @template T
     */
    public function findByTag(string $tag): iterable
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
        foreach ($this->services as $service) {
            foreach ($this->configurators as $configurator) {
                if ($configurator->supports($service)) {
                    $configurator->configure($service, $this);
                }
            }
        }

        foreach ($this->processors as $processors) {
            foreach ($processors as $processor) {
                $processor->process($this);
            }
        }

        $services = [];

        foreach ($this->services as $id => $service) {
            try {
                $metadata = $service->build();

                if ($metadata === null) {
                    continue; // Skip ignored services
                }

                $services[$id] = $metadata;
            } catch (Throwable $e) {
                throw new ContainerBuildException(
                    sprintf('Error building service "%s": %s', $id, $e->getMessage()),
                    previous: $e,
                );
            }
        }

        return new BuiltContainer(
            services: $services,
            aliases: $this->aliases,
        );
    }
}
