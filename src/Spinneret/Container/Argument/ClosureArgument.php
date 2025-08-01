<?php

namespace Arakne\Spinneret\Container\Argument;

use Closure;
use Override;
use Psr\Container\ContainerInterface;

use function sprintf;

/**
 * Represents a value which is wrapped in a Closure and resolved lazily.
 *
 * This type allows to defer the resolution of the value until it is actually needed,
 * which can be useful for performance optimization or to avoid circular dependencies.
 */
final readonly class ClosureArgument implements ArgumentInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The value which will be resolved lazily as a Closure.
         */
        private ArgumentInterface $argument,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): Closure
    {
        return fn (): mixed => $this->argument->resolve($container);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('(fn () => %s)', $this->argument->compile());
    }

    #[Override]
    public function type(): ?string
    {
        return Closure::class;
    }
}
