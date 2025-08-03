<?php

namespace Arakne\Spinneret\Container\Value;

use Attribute;
use Generator;
use Override;
use Psr\Container\ContainerInterface;

use function assert;
use function implode;
use function is_array;
use function sprintf;

/**
 * Represents a new expression to create an object.
 *
 * Unlike {@see Literal} with an object, arguments are resolved dynamically,
 * and do not depend on promoted properties.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class NewExpression implements NestedValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * Class name of the object to create.
         *
         * @var class-string
         */
        public string $className,

        /**
         * Arguments to pass to the constructor of the class.
         * Can be a list or an associative array.
         * If an associative array is used, the keys will be used as named arguments.
         *
         * @var array<mixed>
         */
        public array $arguments = [],
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        $arguments = new DynamicArray($this->arguments)->resolve($container);

        /** @psalm-suppress MixedMethodCall */
        return new ($this->className)(...$arguments);
    }

    #[Override]
    public function compile(): string
    {
        $isList = array_is_list($this->arguments);
        $arguments = [];

        /** @var mixed $value */
        foreach ($this->arguments as $key => $value) {
            if (is_array($value)) {
                $value = new DynamicArray($value);
            }

            if ($value instanceof ValueInterface) {
                $value = $value->compile();
            } else {
                $value = Literal::dump($value);
            }

            if ($isList) {
                $arguments[] = $value;
            } else {
                $arguments[] = $key . ': ' . $value;
            }
        }

        return sprintf('new \%s(%s)', $this->className, implode(', ', $arguments));
    }

    #[Override]
    public function type(): ?string
    {
        return $this->className;
    }

    #[Override]
    public function traverse(): Generator
    {
        $values = [];

        /** @var mixed $value */
        foreach ($this->arguments as $key => $value) {
            if (is_array($value)) {
                $value = new DynamicArray($value);
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

        return new self($this->className, $values);
    }
}
