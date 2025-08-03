<?php

namespace Arakne\Spinneret\Container\Value;

use Attribute;
use Generator;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

use function assert;
use function class_exists;
use function sprintf;

/**
 * Represents access to a property of an object stored in the container.
 * The object is retrieved using its ID, and the property is accessed directly.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class PropertyAccess implements NestedValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The object from which the property will be accessed.
         */
        public ValueInterface $object,

        /**
         * The name of the property to access.
         * This property must exist in the class of the object.
         */
        public string $property,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        /** @psalm-suppress MixedPropertyFetch */
        return $this->object->resolve($container)->{$this->property};
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('%s->%s', $this->object->compile(), $this->property);
    }

    #[Override]
    public function type(): ?string
    {
        $type = $this->object->type();

        if ($type === null || !class_exists($type)) {
            return null;
        }

        try {
            $r = new ReflectionClass($type);
            $type = $r->getProperty($this->property)->getType();

            if (!$type instanceof ReflectionNamedType) {
                return null;
            }

            return $type->getName();
        } catch (ReflectionException) {
            return null;
        }
    }

    #[Override]
    public function traverse(): Generator
    {
        $object = yield $this->object;
        assert($object instanceof ValueInterface || $object === null);

        if ($object === null || $object === $this->object) {
            return $this;
        }

        return new self($object, $this->property);
    }
}
