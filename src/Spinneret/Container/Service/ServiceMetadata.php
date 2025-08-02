<?php

namespace Arakne\Spinneret\Container\Service;

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

        /** @var list<string> */
        public array $tags = [],
        public bool $ignoreIfInvalid = false,
    ) {
        if ($class === null && $factory === null) {
            throw new ContainerBuildException('Service must have a class or a factory.');
        }
    }
}
