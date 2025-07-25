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
use ReflectionFunction;

use function array_is_list;
use function array_values;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;


// @todo: public, shared, inline, preload
final class ServiceBuilder
{
    /**
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

    public function __construct(
        public string $id,

        /**
         * @var class-string
         * @todo make it nullable to allow service without class
         */
        public string $class,
    ) {}

    /**
     * @param class-string $class
     * @return $this
     */
    public function class(string $class): self
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

    public function arg(mixed $value): self
    {
        $this->arguments[] = $value;

        return $this;
    }

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

    public function build(): ServiceMetadata
    {
        return new ServiceMetadata(
            class: $this->class,
            arguments: $this->buildArguments(),
            factory: $this->resolveFactory(),
            tags: $this->buildTags(),
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
