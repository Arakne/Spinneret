<?php

namespace Arakne\Spinneret\Container\Argument;

use Arakne\Spinneret\Container\Exception\ContainerBuildException;
use Override;
use Psr\Container\ContainerInterface;

use function method_exists;
use function sprintf;
use function var_export;

/**
 * Represents an iterable of services tagged with a specific tag.
 * The services are retrieved from the container using the tag.
 *
 * The parameter must allow iterable, and not only an array.
 */
final readonly class TaggedServiceIterator implements ArgumentInterface
{
    public function __construct(
        public string $tag,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): iterable
    {
        // @todo use dedicated interface for container
        if (!method_exists($container, 'findByTag')) {
            throw new ContainerBuildException('Container does not support tagged services.');
        }

        /** @psalm-suppress MixedReturnStatement - TODO: remove when interface will be created */
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
