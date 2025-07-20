<?php

namespace Arakne\Spinneret\Application\Attribute;

use Arakne\Spinneret\Application\AbstractModule;
use Attribute;
use Override;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Post implements ModuleAttributeInterface
{
    public function __construct(
        public string $path,
    ) {}

    #[Override]
    public function register(ReflectionClass $class, AbstractModule $module): void
    {
        $module->post($this->path, $class->name, null);
    }
}
