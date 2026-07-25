<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Arakne\Spinneret\Container\Value\Call;
use Arakne\Spinneret\Container\Value\NewExpression;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Closure;
use InvalidArgumentException;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_values;
use function count;
use function is_array;
use function is_object;

/**
 * Builder for service metadata.
 */
final class ServiceBuilder implements ValidatableInterface
{
    /**
     * List of arguments to pass to the service constructor or factory.
     * This is a 0-indexed array of values.
     * The first argument is at index 0, the second at index 1, and so on.
     *
     * @var list<mixed>
     */
    public array $arguments = [];

    /**
     * Define the factory function or method to used instead of the class constructor.
     *
     * @var ServiceFactoryInterface|Closure|callable-string|null
     */
    public ServiceFactoryInterface|Closure|string|null $factory = null;

    /**
     * Define the service as an inline value.
     *
     * If a {@see ValueInterface} is used, it will be resolved using {@see ValueInterface::resolve()}.
     * If used into a compiled container, the value will be compiled using the {@see ValueInterface::compile()},
     * or {@see Literal::dump()} in case of a literal value.
     *
     * Value service should be `shared`. Non-shared value services may lead to inconsistencies between compiled and non-compiled containers.
     * No autowiring will be applied to the value, so it must be a valid value.
     *
     * @var mixed
     */
    public mixed $value = null;

    /**
     * @var list<string|object>
     */
    public array $tags = [];

    /**
     * Indicates whether the service is public.
     *
     * A public service can be accessed using {@see ContainerInterface::get()},
     * while a private service can only be accessed by using dependency injection through the container.
     *
     * Note: The container will not enforce this visibility, it's simply a hint allowing the container apply optimizations.
     */
    public bool $public = false;

    /**
     * Indicates whether the service is shared.
     *
     * A shared service is a singleton, meaning that only one instance of the service will be created and reused.
     * A non-shared service will create a new instance each time it is requested.
     */
    public bool $shared = true;

    /**
     * Indicates whether the service instantiation should be inlined.
     *
     * If true, in case of compiled container, the service will be instantiated directly in the compiled code,
     * instead of being resolved from the container.
     * This can improve performance, but disallow usage of public or shared services.
     *
     * If false, the service will never be inlined, even if it is safe to do so.
     *
     * If null, this flags will be automatically set to true if it's safe to inline the service:
     * - The service is not shared
     * - The service is private and is used only by a single service
     */
    public ?bool $inline = null;

    /**
     * Indicates whether the service is only defined at runtime.
     *
     * If true, the service will not be instantiated by the container, but must be manually instantiated
     * and set to the container after build.
     * This allows to define services that are not available at compile time, without the compiler to complain about it.
     *
     * @var bool
     */
    public bool $runtime = false;

    /**
     * Ignore the service if it is invalid.
     *
     * If the service cannot be built or instantiated,
     * it will be deleted from the container and not included in the compiled container.
     *
     * This flag is automatically set to true if the service has been auto-registered by the autowiring processor.
     */
    public bool $ignoreIfInvalid = false;

    private ?ReflectionClass $reflection = null;

    public function __construct(
        /**
         * The service ID.
         */
        public readonly string $id,

        /**
         * The class name of the service.
         * This value must be provided if the service does not have a factory.
         *
         * @var class-string|null
         */
        public ?string $class,
    ) {}

    /**
     * Define the service class.
     * Use null to indicate that the service does not have a class (e.g., use a factory, or it's a literal value).
     *
     * @param class-string|null $class
     * @return $this
     */
    public function class(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Define the service factory.
     *
     * The factory can be:
     * - An instance of {@see ServiceFactoryInterface}
     * - An FCC closure referencing a global function (e.g. `my_factory(...)`).
     *   It will be converted to a {@see FunctionServiceFactory} using function name.
     * - An FCC closure referencing a method of a class (e.g. `new MyFactory()->myFactory(...)`).
     *   It will be converted to a {@see MethodServiceFactory}, inlining the factory instance into a {@see Literal},
     *   and using the method name as factory method.
     * - An FCC closure referencing a static method (e.g. `MyFactory::myFactory(...)`).
     *   It will be converted to a {@see StaticMethodServiceFactory} using the class name and method name.
     * - A callable string, which will be converted to a {@see FunctionServiceFactory}.
     *
     * Usage:
     * ```php
     * $service->factory(new FunctionServiceFactory('my_factory')); // Directly use a service factory instance.
     * $service->factory(my_factory(...)); // Same as above, but using a FCC
     * $service->factory(new MyFactory()->create(...)); // Use a method of an instance as factory.
     * $service->factory(MyFactory::create(...)); // Use a static method as factory.
     * $service->factory('my_factory'); // Use a callable string as factory.
     * ```
     *
     * @param ServiceFactoryInterface|Closure|callable-string|null $factory
     * @return $this
     */
    public function factory(ServiceFactoryInterface|Closure|string|null $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Define the service as an inline value.
     *
     * If a {@see ValueInterface} is used, it will be resolved using {@see ValueInterface::resolve()}.
     * If used into a compiled container, the value will be compiled using the {@see ValueInterface::compile()},
     * or {@see Literal::dump()} in case of a literal value.
     *
     * Note: Value service should be `shared`. Non-shared value services may lead to inconsistencies between compiled and non-compiled containers.
     *
     * @param mixed $value The value to set as the service value.
     * @return $this
     */
    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Add a new argument to the service constructor or factory.
     * Use {@see ValueInterface} to provide a dynamic argument.
     *
     * Note: to modify an existing argument, directly modify the `arguments` property.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function arg(mixed $value): self
    {
        $this->arguments[] = $value;

        return $this;
    }

    /**
     * Modify an existing argument at the given index.
     *
     * @param non-negative-int $index The argument index to modify. This value is 0-based, meaning that the first argument is at index 0, the second at index 1, and so on.
     * @param mixed $value The new value for the argument.
     *
     * @return $this
     */
    public function set(int $index, mixed $value): self
    {
        // @phpstan-ignore assign.propertyType
        $this->arguments[$index] = $value;

        return $this;
    }

    /**
     * Add a new tag to the service.
     *
     * @param string|object $tag The tag name, or object.
     * @return $this
     */
    public function tag(string|object $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Define the service as runtime.
     * Runtime services are not instantiated by the container, but manually set after the container is built.
     *
     * @param bool $runtime
     * @return $this
     */
    public function runtime(bool $runtime = true): self
    {
        $this->runtime = $runtime;

        return $this;
    }

    /**
     * Define the service as public.
     * Public services can be accessed using {@see ContainerInterface::get()},
     * while private services can only be accessed by using dependency injection through the container.
     *
     * @param bool $public
     * @return $this
     */
    public function public(bool $public = true): self
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Define the service as shared.
     * Shared services are singletons, meaning that only one instance of the service will be created on the container.
     *
     * @param bool $shared
     * @return $this
     */
    public function shared(bool $shared = true): self
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * Always inline this service if possible.
     *
     * If enabled, all reference to this service will be replaced with direct instantiation,
     * using the class constructor or the factory method.
     *
     * Note: The service will be automatically inlined if it is private and used only once.
     *       Manually inlining a shared service will make the shared flag useless, as the service will be instantiated
     *       each time it is requested as dependency.
     *
     * @param bool $inline
     * @return $this
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * Ignore the service if it's invalid.
     *
     * The container will detect if the service cannot be built or instantiated,
     * and will remove it instead of throwing an exception.
     *
     * This flag is automatically set to true if the service has been auto-registered by the autowiring system.
     *
     * @param bool $ignoreIfInvalid
     * @return $this
     */
    public function ignorable(bool $ignoreIfInvalid = true): self
    {
        $this->ignoreIfInvalid = $ignoreIfInvalid;

        return $this;
    }

    /**
     * Convert the factory property to a proper ServiceFactoryInterface instance.
     *
     * @return ServiceFactoryInterface|null
     */
    public function resolveFactory(): ?ServiceFactoryInterface
    {
        $factory = $this->factory;

        if ($factory === null || $factory instanceof ServiceFactoryInterface) {
            return $factory;
        }

        return $this->factory = ServiceFactoryConverter::convert($factory);
    }

    /**
     * Get the reflection of the service class.
     *
     * @return ReflectionClass|null The reflection of the service class, or null if the class is not set.
     */
    public function reflection(): ?ReflectionClass
    {
        if ($this->reflection?->name === $this->class) {
            return $this->reflection;
        }

        return $this->reflection = $this->class !== null
            ? new ReflectionClass($this->class)
            : null
        ;
    }

    /**
     * Check if the service definition is valid.
     *
     * A service is considered valid if:
     * - If the service is runtime, it is always valid.
     * - It has a class or a factory defined.
     * - If it has a class, the class exists and is instantiable.
     * - If it has a factory, the factory is a callable
     * - The count of arguments matches the constructor or factory method signature.
     * - Arguments are valid (e.g. not invalid references or invalid values).
     *
     * @param ContainerBuilder $builder
     * @return bool
     */
    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        if ($this->runtime) {
            return true;
        }

        if ($this->value !== null) {
            return !$this->value instanceof ValidatableInterface || $this->value->validate($builder);
        }

        $factory = $this->resolveFactory();

        if ($this->class === null && $factory === null) {
            return false;
        }

        if ($factory !== null) {
            if (
                $factory instanceof ValidatableInterface
                && !$factory->validate($builder)
            ) {
                return false;
            }

            $parameters = $factory->parameters();
        } else {
            $reflection = $this->reflection();

            if (
                !$reflection
                || !$reflection->isInstantiable()
            ) {
                return false;
            }

            $constructor = $reflection->getConstructor();

            if ($constructor?->isPublic() === false) {
                return false;
            }

            /** @var ReflectionMethod|null $constructor */

            $parameters = $constructor?->getParameters();
        }

        if ($parameters !== null) {
            $requiredCount = 0;

            foreach ($parameters as $parameter) {
                if (!$parameter->isOptional()) {
                    $requiredCount++;
                }
            }

            if (count($this->arguments) < $requiredCount) {
                return false;
            }
        }

        /** @var mixed $argument */
        foreach ($this->arguments as $argument) {
            if ($argument instanceof ValidatableInterface && !$argument->validate($builder)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build the service metadata.
     *
     * @return ServiceMetadata|null The service metadata, or null if the service is ignored.
     * @throws ContainerBuildException When the service cannot be built.
     */
    public function build(): ?ServiceMetadata
    {
        if ($this->runtime) {
            return null;
        }

        if ($this->ignoreIfInvalid && $this->factory === null && $this->class === null && $this->value === null) {
            return null;
        }

        if ($this->value !== null) {
            return new ServiceMetadata(
                class: $this->class,
                value: ($this->value instanceof ValueInterface ? $this->value : new Literal($this->value)),
                tags: $this->buildTags(),
                ignoreIfInvalid: $this->ignoreIfInvalid,
                shared: $this->shared,
            );
        }

        try {
            return new ServiceMetadata(
                class: $this->class,
                arguments: $this->buildArguments(),
                factory: $this->resolveFactory(),
                tags: $this->buildTags(),
                ignoreIfInvalid: $this->ignoreIfInvalid,
                shared: $this->shared,
            );
        } catch (Throwable $e) {
            if ($this->ignoreIfInvalid) {
                return null; // Ignore the service if it is invalid
            }

            throw $e;
        }
    }

    /**
     * Inline the service instantiation as a value.
     *
     * @return ValueInterface|null The inline value representing the service instantiation, or null if the service cannot be inlined.
     */
    public function asInlineValue(ContainerBuilder $builder): ?ValueInterface
    {
        if (!$this->validate($builder)) {
            return null;
        }

        if ($this->value !== null) {
            return $this->value instanceof ValueInterface
                ? $this->value
                : new Literal($this->value)
            ;
        }

        try {
            $factory = $this->resolveFactory();

            if ($factory !== null) {
                return new Call($factory, $this->buildArguments());
            }

            if ($this->class === null) {
                return null;
            }

            return new NewExpression($this->class, $this->buildArguments());
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<ValueInterface>
     */
    private function buildArguments(): array
    {
        $arguments = [];

        /** @var mixed $argument */
        foreach ($this->arguments as $argument) {
            if ($argument instanceof ValueInterface) {
                $arguments[] = $argument;
            } elseif (is_array($argument)) {
                $arguments[] = new DynamicArray($argument);
            } else {
                $arguments[] = new Literal($argument);
            }
        }

        return $arguments;
    }

    /**
     * @return list<string>
     */
    private function buildTags(): array
    {
        $tags = [];

        foreach ($this->tags as $tag) {
            if (is_object($tag)) {
                $tag = $tag::class;
            }

            $tags[$tag] = $tag;
        }

        return array_values($tags);
    }
}
