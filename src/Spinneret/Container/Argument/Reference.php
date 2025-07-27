<?php

namespace Arakne\Spinneret\Container\Argument;

use Override;
use Psr\Container\ContainerInterface;
use Throwable;

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
        public bool $nullOnInvalid = false,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        try {
            return $container->get($this->id);
        } catch (Throwable $e) {
            if ($this->nullOnInvalid) {
                return null;
            }

            throw $e;
        }
    }

    #[Override]
    public function compile(): string
    {
        if ($this->nullOnInvalid) {
            return sprintf('$this->getOrNull(%s)', var_export($this->id, true));
        } else {
            return sprintf('$this->get(%s)', var_export($this->id, true));
        }
    }

    #[Override]
    public function type(): ?string
    {
        return class_exists($this->id) ? $this->id : null;
    }
}
