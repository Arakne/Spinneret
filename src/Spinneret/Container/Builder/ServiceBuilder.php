<?php

namespace Arakne\Spinneret\Container\Builder;

use Arakne\Spinneret\Container\Argument\ArgumentInterface;
use Arakne\Spinneret\Container\Argument\DynamicArray;
use Arakne\Spinneret\Container\Argument\Literal;
use Arakne\Spinneret\Container\Argument\Reference;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\Service\FunctionServiceFactory;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Arakne\Spinneret\Container\Service\ServiceMetadata;
use Arakne\Spinneret\Container\Service\StaticMethodServiceFactory;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;

use function array_is_list;
use function array_values;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;

/**
 * Builder for service metadata.
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
     * @var ServiceFactoryInterface|Closure|callable-string|list{ArgumentInterface|class-string, string}|null
     */
    public ServiceFactoryInterface|Closure|string|array|null $factory = null;

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
     * @param ServiceFactoryInterface|Closure|callable-string|list{ArgumentInterface|class-string, string}|null $factory
     * @return $this
     */
    public function factory(ServiceFactoryInterface|Closure|string|array|null $factory): self
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Add a new argument to the service constructor or factory.
     * Use {@see ArgumentInterface} to provide a dynamic argument.
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
     * @return ServiceFactoryInterface|null
     * @psalm-suppress DocblockTypeContradiction
     */
    // @todo: externalize this to a separate class
    public function resolveFactory(): ?ServiceFactoryInterface
    {
        $factory = $this->factory;

        if ($factory === null || $factory instanceof ServiceFactoryInterface) {
            return $factory;
        }

        if (is_string($factory)) {
            return $this->factory = new FunctionServiceFactory($factory);
        }

        if (is_array($factory)) {
            if (
                !array_is_list($factory)
                || count($factory) !== 2
                || (!is_string($factory[0]) && !$factory[0] instanceof ArgumentInterface)
                || !is_string($factory[1])
            ) {
                throw new ContainerBuildException('Factory must be a callable or an array with two elements: [class, method].');
            }

            return $this->factory = $factory[0] instanceof ArgumentInterface
                ? new MethodServiceFactory($factory[0], $factory[1])
                : new StaticMethodServiceFactory($factory[0], $factory[1])
            ;
        }

        // @todo handle global functions
        $reflection = new ReflectionFunction($factory);
        $calledClass = $reflection->getClosureCalledClass()?->getName();
        $methodName = $reflection->getName();

        // @todo do not use calledClass for instance methods to resolve the service ID
        // @todo In this case, prefer to use new Literal with closure this, and let the compiler inlining the factory instanciation.
        if ($calledClass !== null && method_exists($calledClass, $methodName)) {
            return $reflection->isStatic()
                ? new StaticMethodServiceFactory($calledClass, $reflection->getName())
                : new MethodServiceFactory(new Reference($calledClass), $reflection->getName())
            ;
        }

        return new FunctionServiceFactory($factory);
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
     * @throws ContainerBuildException When the service cannot be built.
     */
    public function build(): ServiceMetadata
    {
        // @todo handle ignoreIfInvalid
        return new ServiceMetadata(
            class: $this->class,
            arguments: $this->buildArguments(),
            factory: $this->resolveFactory(),
            tags: $this->buildTags(),
            ignoreIfInvalid: $this->ignoreIfInvalid,
        );
    }

    /**
     * @return list<ArgumentInterface>
     */
    private function buildArguments(): array
    {
        $arguments = [];

        /** @var mixed $argument */
        foreach ($this->arguments as $argument) {
            if ($argument instanceof ArgumentInterface) {
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
