<?php

namespace Arakne\Spinneret\Container\Argument;

use Override;
use Psr\Container\ContainerInterface;

use function sprintf;
use function var_export;

/**
 * Access to an array offset from a dynamic value.
 * This is equivalent of {@see PropertyAccess} but for arrays with `[]` operator.
 */
final readonly class ArrayOffset implements ArgumentInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The array to access.
         * The resolved value must be an array or an object implementing `ArrayAccess`.
         */
        public ArgumentInterface $array,

        /**
         * The offset to access.
         * This can be a string or an integer, depending on the array type.
         */
        public int|string $offset,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        /** @psalm-suppress MixedArrayAccess */
        return $this->array->resolve($container)[$this->offset];
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('%s[%s]', $this->array->compile(), var_export($this->offset, true));
    }

    #[Override]
    public function type(): ?string
    {
        // Type cannot be resolved because array is dynamic.
        return null;
    }
}
