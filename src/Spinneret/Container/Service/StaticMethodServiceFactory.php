<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function assert;
use function class_exists;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * Create a service using a static method call.
 */
final readonly class StaticMethodServiceFactory implements ServiceFactoryInterface, ValidatableInterface
{
    use FactoryHelperTrait;

    public function __construct(
        /**
         * The class name or a value that resolves to a class name.
         *
         * @var class-string|ValueInterface
         */
        public string|ValueInterface $class,
        public string $method,
    ) {}

    #[Override]
    public function create(ContainerInterface $container, array $arguments): mixed
    {
        $class = $this->class instanceof ValueInterface ? $this->class->resolve($container) : $this->class;
        assert(is_string($class) && class_exists($class));

        /** @psalm-suppress MixedMethodCall */
        return $class::{$this->method}(...$arguments);
    }

    #[Override]
    public function parameters(): ?array
    {
        if (!is_string($this->class)) {
            return null;
        }

        try {
            return new ReflectionMethod($this->class, $this->method)->getParameters();
        } catch (ReflectionException) {
            return null;
        }
    }

    #[Override]
    public function compile(string $arguments): string
    {
        $class = is_string($this->class) ? $this->class : $this->class->compile();

        return sprintf('\%s::%s(%s)', $class, $this->method, $arguments);
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        if (!is_string($this->class)) {
            return true;
        }

        return class_exists($this->class) && method_exists($this->class, $this->method);
    }
}
