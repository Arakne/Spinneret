<?php

namespace Arakne\Spinneret\Event\Attribute;

use Arakne\Spinneret\Container\Attribute\ServiceConfiguratorAttributeInterface;
use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ServiceBuilder;
use Attribute;
use LogicException;
use Override;
use ReflectionException;
use ReflectionMethod;

use ReflectionNamedType;
use ReflectionUnionType;

use function array_map;
use function count;
use function sprintf;

/**
 * Define the callable-class or method as an event listener.
 *
 * If used on a class, it must have a public `__invoke` method.
 *
 * If the event class is not explicitly defined on the attribute, it will be inferred from the method signature:
 * - The method must have exactly one parameter.
 * - The parameter must have a type
 * - If the class is an union type, it must have at least one class type.
 * - If the class is not a union type, it must be a class type.
 *
 * Usage:
 * ```php
 * #[EventListener]
 * class MyEventListener
 * {
 *     public function __invoke(MyEvent $event): void
 *     {
 *         // Handle the event
 *     }
 * }
 *
 * class MultipleListeners
 * {
 *     #[EventListener]
 *     public function onFoo(Foo $event): void
 *     {
 *         // Handle the event
 *     }
 *
 *     #[EventListener]
 *     public function onMultipleEvents(Foo|Bar|Baz $event): void
 *     {
 *         // Handle the event
 *     }
 *
 *     #[EventListener(Bar::class)]
 *     public function onExplicitlyDefined(object $event): void
 *     {
 *         // Handle the event
 *     }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class EventListener implements ServiceConfiguratorAttributeInterface
{
    public function __construct(
        /**
         * The event class this listener is for.
         * If null, it will be inferred from the method signature.
         *
         * @var class-string|null
         */
        public ?string $eventClass = null,
    ) {}

    #[Override]
    public function configure(ServiceBuilder $service, ContainerBuilder $container): void
    {
        $service
            ->ignorable(false)
            ->public()
        ;

        if ($this->eventClass !== null) {
            $service->tag($this);
            return;
        }

        try {
            $method = $service->reflection()?->getMethod('__invoke');
        } catch (ReflectionException) {
            $method = null;
        }

        if ($method === null) {
            throw new LogicException(
                sprintf(
                    'Service %s must have a public __invoke method to be used as an event listener.',
                    $service->id
                )
            );
        }

        foreach (self::resolveEventClasses($method) as $eventClass) {
            $service->tag(new self($eventClass));
        }
    }

    /**
     * @param ReflectionMethod $reflectionMethod
     * @return list<self>
     */
    public function resolveWithReflectionMethod(ReflectionMethod $reflectionMethod): array
    {
        if ($this->eventClass !== null) {
            return [$this];
        }

        return array_map(
            static fn (string $eventClass): self => new self($eventClass),
            self::resolveEventClasses($reflectionMethod)
        );
    }

    /**
     * @param ReflectionMethod $method
     * @return list<class-string>
     */
    private static function resolveEventClasses(ReflectionMethod $method): array
    {
        if ($method->getNumberOfParameters() !== 1) {
            throw new LogicException(
                sprintf(
                    'Method %s must have exactly one parameter to be used as an event listener.',
                    $method->getName()
                )
            );
        }

        $type = $method->getParameters()[0]->getType();

        if ($type === null) {
            throw new LogicException(
                sprintf(
                    'Method %s must have a single parameter with a class type to be used as an event listener.',
                    $method->getName()
                )
            );
        }

        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $classes = [];

        foreach ($types as $type) {
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $classes[] = $type->getName();
        }

        if (count($classes) === 0) {
            throw new LogicException(
                sprintf(
                    'Method %s must have a single parameter with a class type to be used as an event listener.',
                    $method->getName()
                )
            );
        }

        /** @var list<class-string> $classes */
        return $classes;
    }
}
