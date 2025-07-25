<?php

namespace Arakne\Spinneret\Container\Argument;

use Override;
use Psr\Container\ContainerInterface;

use function class_exists;
use function sprintf;
use function var_export;

/**
 * Represents a reference to an object stored in the container.
 * The object is retrieved using its ID, which must be a valid service ID.
 */
final readonly class Reference implements ArgumentInterface
{
    public function __construct(
        public string $id,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        return $container->get($this->id);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('$this->get(%s)', var_export($this->id, true));
    }

    #[Override]
    public function type(): ?string
    {
        return class_exists($this->id) ? $this->id : null;
    }
}
