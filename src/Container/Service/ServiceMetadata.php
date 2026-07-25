<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Exception\ServiceNotFoundException;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Arakne\Spinneret\Container\Exception\ContainerBuildException;

final readonly class ServiceMetadata
{
    public function __construct(
        /** @var class-string|null */
        public ?string $class,

        /** @var list<ValueInterface> */
        public array $arguments = [],
        public ?ServiceFactoryInterface $factory = null,
        public ?ValueInterface $value = null,

        /** @var list<string> */
        public array $tags = [],

        /**
         * If true, and the service cannot be compiled / built, it will be ignored,
         * resulting in {@see ServiceNotFoundException}.
         */
        public bool $ignoreIfInvalid = false,

        /**
         * If true, the instance of the service will be shared across the container.
         * So, calling {@see ContainerInterface::get()} multiple times will return the same instance.
         */
        public bool $shared = true,
    ) {
        if ($class === null && $factory === null && $value === null) {
            throw new ContainerBuildException('Service must have a class or a factory or a value.');
        }
    }
}
