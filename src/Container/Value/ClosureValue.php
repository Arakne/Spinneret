<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Attribute;
use Closure;
use Generator;
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
final readonly class ClosureValue implements NestedValueInterface, ValidatableInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The value which will be resolved lazily as a Closure.
         */
        public ValueInterface $value,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): Closure
    {
        return fn(): mixed => $this->value->resolve($container);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('(fn () => %s)', $this->value->compile());
    }

    #[Override]
    public function type(): string
    {
        return Closure::class;
    }

    #[Override]
    public function traverse(): Generator
    {
        $value = yield $this->value;

        if ($value === null || $value === $this->value) {
            return $this;
        }

        return new self($value);
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        return !$this->value instanceof ValidatableInterface || $this->value->validate($builder);
    }
}
