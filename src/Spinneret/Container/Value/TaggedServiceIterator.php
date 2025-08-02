<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Attribute;
use Override;
use Psr\Container\ContainerInterface;

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
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): iterable
    {
        if (!$container instanceof SpinneretContainerInterface) {
            throw new ContainerBuildException('Container does not support tagged services.');
        }

        return $container->findByTag($this->tag);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('$this->findByTag(%s)', var_export($this->tag, true));
    }

    #[Override]
    public function type(): ?string
    {
        // The type is "iterable" which is not an actual type but an union type.
        // So we return null to indicate that we don't know the type.
        return null;
    }
}
