<?php

namespace Arakne\Spinneret\Container\Service;

use Override;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

use function sprintf;

/**
 * Create a service using a static method call.
 */
final readonly class StaticMethodServiceFactory implements ServiceFactoryInterface
{
    use FactoryHelperTrait;

    public function __construct(
        /**
         * @var class-string
         */
        public string $class,
        public string $method,
    ) {}

    #[Override]
    public function create(ContainerInterface $container, array $arguments): mixed
    {
        /** @psalm-suppress MixedMethodCall */
        return $this->class::{$this->method}(...$arguments);
    }

    #[Override]
    public function parameters(): ?array
    {
        try {
            return new ReflectionMethod($this->class, $this->method)->getParameters();
        } catch (ReflectionException) {
            return null;
        }
    }

    #[Override]
    public function compile(string $arguments): string
    {
        return sprintf('\%s::%s(%s)', $this->class, $this->method, $arguments);
    }
}
