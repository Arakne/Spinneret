<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function class_exists;
use function method_exists;
use function sprintf;

/**
 * Create a service that calls a method on an object.
 */
final readonly class MethodServiceFactory implements ServiceFactoryInterface, ValidatableInterface
{
    use FactoryHelperTrait;

    public function __construct(
        public ValueInterface $object,
        public string $method,
    ) {}

    #[Override]
    public function create(ContainerInterface $container, array $arguments): mixed
    {
        /** @psalm-suppress MixedMethodCall */
        return $this->object->resolve($container)->{$this->method}(...$arguments);
    }

    #[Override]
    public function parameters(): ?array
    {
        $type = $this->object->type();

        if ($type === null || !class_exists($type)) {
            return null;
        }

        try {
            return new ReflectionMethod($type, $this->method)->getParameters();
        } catch (ReflectionException) {
            return null;
        }
    }

    #[Override]
    public function compile(string $arguments): string
    {
        return sprintf('%s->%s(%s)', $this->object->compile(), $this->method, $arguments);
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        if ($this->object instanceof ValidatableInterface && !$this->object->validate($builder)) {
            return false;
        }

        $type = $this->object->type();

        if ($type === null) {
            return true;
        }

        return class_exists($type) && method_exists($type, $this->method);
    }
}
