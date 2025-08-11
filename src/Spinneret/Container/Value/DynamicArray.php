<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Attribute;
use Generator;
use Override;
use Psr\Container\ContainerInterface;

use function array_is_list;
use function assert;
use function is_array;
use function var_export;

/**
 * Represents an array, with inner values that can be resolved.
 * If you want to represent a constant array, use {@see Literal} instead.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class DynamicArray implements NestedValueInterface, ValidatableInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * @var array<array-key, mixed>
         */
        public array $values,
    ) {}

    /**
     * @psalm-suppress MixedAssignment
     */
    #[Override]
    public function resolve(ContainerInterface $container): array
    {
        $values = [];

        /** @var mixed $value */
        foreach ($this->values as $key => $value) {
            if (is_array($value)) {
                $value = new self($value);
            }

            if ($value instanceof ValueInterface) {
                /** @var mixed $value */
                $value = $value->resolve($container);
            }

            /** @var mixed $value */
            $values[$key] = $value;
        }

        return $values;
    }

    #[Override]
    public function compile(): string
    {
        $isList = array_is_list($this->values);
        $output = '[';

        /** @var mixed $value */
        foreach ($this->values as $key => $value) {
            if (is_array($value)) {
                $value = new self($value);
            }

            if ($value instanceof ValueInterface) {
                $value = $value->compile();
            } else {
                $value = Literal::dump($value);
            }

            if ($isList) {
                $output .= $value . ', ';
            } else {
                $output .= var_export($key, true) . ' => ' . $value . ', ';
            }
        }

        return $output . ']';
    }

    #[Override]
    public function type(): ?string
    {
        return 'array';
    }

    #[Override]
    public function traverse(): Generator
    {
        $values = [];

        /** @var mixed $value */
        foreach ($this->values as $key => $value) {
            if (is_array($value)) {
                $value = new self($value);
            }

            if ($value instanceof ValueInterface) {
                $newValue = yield $value;
                assert($newValue instanceof ValueInterface || $newValue === null);
            } else {
                /** @var mixed */
                $newValue = $value;
            }

            /** @var mixed */
            $values[$key] = $newValue ?? $value;
        }

        return new self($values);
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        /** @var mixed $value */
        foreach ($this->values as $value) {
            if (is_array($value)) {
                $value = new self($value);
            }

            if ($value instanceof ValidatableInterface && !$value->validate($builder)) {
                return false;
            }
        }

        return true;
    }
}
