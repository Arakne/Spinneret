<?php

namespace Arakne\Spinneret\Application\Attribute;

use Arakne\Spinneret\Application\AbstractModule;
use Arakne\Spinneret\Presenter\PresenterInterface;
use Attribute;
use InvalidArgumentException;
use Override;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Presenter implements ModuleAttributeInterface
{
    public function __construct(
        /**
         * The DTO request class that the presenter will handle.
         *
         * @var class-string
         */
        public string $requestClass,
    ) {}

    #[Override]
    public function register(ReflectionClass $class, AbstractModule $module): void
    {
        if (!$class->implementsInterface(PresenterInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Class %s must implement %s to be registered as a presenter.',
                    $class->name,
                    PresenterInterface::class
                )
            );
        }

        $module->presenter($this->requestClass, $class->name);
    }
}
