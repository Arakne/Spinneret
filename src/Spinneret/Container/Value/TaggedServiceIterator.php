<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Attribute;
use Override;
use Psr\Container\ContainerInterface;

use function iterator_to_array;
use function sprintf;
use function var_export;

/**
 * Represents an iterable of services tagged with a specific tag.
 * The services are retrieved from the container using the tag.
 *
 * The parameter must allow iterable, and not only an array.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class TaggedServiceIterator implements ValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        public string $tag,

        /**
         * Force the result to be an array.
         */
        public bool $asArray = false,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): iterable
    {
        if (!$container instanceof SpinneretContainerInterface) {
            throw new ContainerBuildException('Container does not support tagged services.');
        }

        $values = $container->findByTag($this->tag);

        /** @psalm-suppress InvalidArgument */
        return $this->asArray ? iterator_to_array($values) : $values;
    }

    #[Override]
    public function compile(): string
    {
        $code = sprintf('$this->findByTag(%s)', var_export($this->tag, true));

        if ($this->asArray) {
            $code = sprintf('\iterator_to_array(%s)', $code);
        }

        return $code;
    }

    #[Override]
    public function type(): ?string
    {
        // The type is "iterable" which is not an actual type but an union type.
        // So we return null to indicate that we don't know the type.
        return null;
    }
}
