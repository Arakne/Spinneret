<?php

namespace Arakne\Spinneret\Application\Attribute;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\View\ViewRendererInterface;
use Attribute;
use InvalidArgumentException;
use Override;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Renderer implements ModuleAttributeInterface
{
    public function __construct(
        /**
         * The DTO response class that the renderer will handle.
         *
         * @var class-string
         */
        public string $responseClass,
    ) {}

    #[Override]
    public function register(ReflectionClass $class, AbstractModule $module): void
    {
        // @todo allow to resolve the response class from the renderer class if invokable
        if (!$class->isSubclassOf(ViewRendererInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Class %s must implement %s to be registered as a renderer.',
                    $class->name,
                    ViewRendererInterface::class,
                )
            );
        }

        $module->renderer($this->responseClass, $class->name);
    }
}
