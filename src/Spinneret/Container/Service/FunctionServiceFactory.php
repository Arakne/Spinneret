<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Closure;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionFunction;

use function is_callable;
use function is_string;
use function sprintf;

/**
 * Create a service using a simple function or Closure.
 *
 * Note: only string function can be compiled.
 */
final readonly class FunctionServiceFactory implements ServiceFactoryInterface, ValidatableInterface
{
    use FactoryHelperTrait;

    public function __construct(
        /**
         * Callable string function or Closure to be executed.
         *
         * @var callable-string|Closure
         */
        public string|Closure $function,
    ) {}

    #[Override]
    public function create(ContainerInterface $container, array $arguments): mixed
    {
        return ($this->function)(...$arguments);
    }

    #[Override]
    public function parameters(): array
    {
        return new ReflectionFunction($this->function)->getParameters();
    }

    #[Override]
    public function compile(string $arguments): string
    {
        if (!is_string($this->function)) {
            throw new ContainerBuildException('Cannot compile a function that is not a string.');
        }

        return sprintf('\%s(%s)', $this->function, $arguments);
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        return is_callable($this->function);
    }
}
