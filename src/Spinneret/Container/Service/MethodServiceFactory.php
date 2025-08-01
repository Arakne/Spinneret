<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Argument\ArgumentInterface;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function class_exists;
use function sprintf;

/**
 * Create a service that calls a method on an object.
 */
final readonly class MethodServiceFactory implements ServiceFactoryInterface
{
    use FactoryHelperTrait;

    public function __construct(
        public ArgumentInterface $object,
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
}
