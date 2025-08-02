<?php

namespace Arakne\Spinneret\Container\Value;

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
final readonly class Reference implements ValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The ID of the service to reference.
         * This ID must correspond to a service registered in the container.
         */
        public string $id,

        /**
         * Use null if the service is not found or invalid.
         */
        public bool $nullOnInvalid = false,

        /**
         * Default value to return if the service is not found or invalid.
         *
         * Note: if the default value is null, use `nullOnInvalid` instead.
         */
        public mixed $defaultValueOnInvalid = null,
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

            if ($this->defaultValueOnInvalid !== null) {
                return $this->defaultValueOnInvalid;
            }

            throw $e;
        }
    }

    #[Override]
    public function compile(): string
    {
        if ($this->nullOnInvalid) {
            return sprintf('$this->getOrNull(%s)', var_export($this->id, true));
        } elseif ($this->defaultValueOnInvalid !== null) {
            return sprintf('($this->getOrNull(%s) ?? %s)', var_export($this->id, true), Literal::dump($this->defaultValueOnInvalid));
        } else {
            return sprintf('$this->get(%s)', var_export($this->id, true));
        }
    }

    #[Override]
    public function type(): ?string
    {
        return class_exists($this->id) ? $this->id : null;
    }

    /**
     * Change the ID of the referenced service.
     *
     * @param string $id The new ID of the service to reference.
     * @return self
     */
    public function withId(string $id): self
    {
        return new self($id, $this->nullOnInvalid, $this->defaultValueOnInvalid);
    }
}
