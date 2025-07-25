<?php

namespace Arakne\Spinneret\Container\Argument;

use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function class_exists;
use function sprintf;
use function var_export;

/**
 * Represents access to a property of an object stored in the container.
 * The object is retrieved using its ID, and the property is accessed directly.
 */
final readonly class PropertyAccess implements ArgumentInterface
{
    public function __construct(
        public string $id,
        public string $property,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        /** @psalm-suppress MixedPropertyFetch */
        return $container->get($this->id)->{$this->property};
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('$this->get(%s)->%s', var_export($this->id, true), $this->property);
    }

    #[Override]
    public function type(): ?string
    {
        if (!class_exists($this->id)) {
            return null;
        }

        try {
            $r = new ReflectionClass($this->id);
            $type = $r->getProperty($this->property)->getType();

            if (!$type instanceof ReflectionNamedType) {
                return null;
            }

            return $type->getName();
        } catch (ReflectionException) {
            return null;
        }
    }
}
