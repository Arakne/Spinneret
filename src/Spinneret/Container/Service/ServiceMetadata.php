<?php

namespace Arakne\Spinneret\Container\Service;

use Arakne\Spinneret\Container\Argument\ArgumentInterface;

final readonly class ServiceMetadata
{

    public function __construct(
        /** @var class-string */
        public string $class,

        /** @var list<ArgumentInterface> */
        public array $arguments = [],
        public ?ServiceFactoryInterface $factory = null,

        /** @var list<string> */
        public array $tags = [],
    ) {}
}
