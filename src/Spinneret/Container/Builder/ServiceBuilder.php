<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Throwable;

use function array_values;
use function is_array;
use function is_object;

/**
 * Builder for service metadata.
 *
 * @todo add fluent setters for all properties + handle "value" service (i.e. use constant value instead of class or factory).
 */
final class ServiceBuilder
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
     * @var ServiceFactoryInterface|Closure|callable-string|null
     */
    public ServiceFactoryInterface|Closure|string|null $factory = null;

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
     *
     * @todo: not implemented yet.
     */
    public bool $public = false;

    /**
     * Indicates whether the service is shared.
     *
     * A shared service is a singleton, meaning that only one instance of the service will be created and reused.
     * A non-shared service will create a new instance each time it is requested.
     *
     * @todo: not implemented yet.
     */
    public bool $shared = true;

    /**
     * Indicates whether the service instantiation should be inlined.
     *
     * If true, in case of compiled container, the service will be instantiated directly in the compiled code,
     * instead of being resolved from the container.
     * This can improve performance, but disallow usage of public or shared services.
     *
     * This flags will be automatically set to true if it's safe to inline the service:
     * - The service is not shared
     * - The service is private and is used only by a single service
     *
     * @todo: not implemented yet.
     */
    public bool $inline = false;

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
     * @param ServiceFactoryInterface|Closure|callable-string|null $factory
     * @return $this
     */
    public function factory(ServiceFactoryInterface|Closure|string|null $factory): self
    {
        $this->factory = $factory;

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
        /** @psalm-suppress PropertyTypeCoercion */
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

        try {
            // @todo handle ignoreIfInvalid
            return new ServiceMetadata(
                class: $this->class,
                arguments: $this->buildArguments(),
                factory: $this->resolveFactory(),
                tags: $this->buildTags(),
                ignoreIfInvalid: $this->ignoreIfInvalid,
            );
        } catch (Throwable $e) {
            if ($this->ignoreIfInvalid) {
                return null; // Ignore the service if it is invalid
            }

            throw $e;
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
