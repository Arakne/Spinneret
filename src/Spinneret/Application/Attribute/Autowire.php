<?php

namespace Arakne\Spinneret\Application\Attribute;

use Arakne\Spinneret\Application\AbstractModule;
use Attribute;
use Override;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Autowire implements ModuleAttributeInterface
{
    public function __construct(
        public bool $public = false,
        /** @var array<array-key, string|array<string, scalar>|list<array<string, scalar>>> */
        public array $tags = [],
        /** @var list<class-string> */
        public array $aliases = [],
    ) {}

    #[Override]
    public function register(ReflectionClass $class, AbstractModule $module): void
    {
        $module->autowire(
            $class->name,
            $this->public,
            $this->tags,
            $this->aliases,
        );
    }
}
