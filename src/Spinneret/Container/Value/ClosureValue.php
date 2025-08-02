<?php

namespace Arakne\Spinneret\Container\Value;

use Attribute;
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
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class ClosureValue implements ValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The value which will be resolved lazily as a Closure.
         */
        private ValueInterface $argument,
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
