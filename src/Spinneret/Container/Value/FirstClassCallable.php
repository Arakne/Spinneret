<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Builder\ValidatableInterface;
use Arakne\Spinneret\Container\Service\MethodServiceFactory;
use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Closure;
use Generator;
use Override;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * Use a function or {@see ServiceFactoryInterface} as {@see Closure} using first-class callable syntax on compiled container.
 */
final readonly class FirstClassCallable implements ValueInterface, NestedValueInterface, ValidatableInterface
{
    public function __construct(
        /**
         * The function or method to call.
         * If the value is not a {@see ServiceFactoryInterface}, it will be converted to one using {@see ServiceFactoryConverter}.
         *
         * @var ServiceFactoryInterface|Closure|callable-string
         */
        public ServiceFactoryInterface|Closure|string $function,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): Closure
    {
        if ($this->function instanceof Closure) {
            return $this->function;
        }

        return function (mixed ...$args) use ($container): mixed {
            $factory = ServiceFactoryConverter::convert($this->function);

            /** @psalm-suppress ArgumentTypeCoercion */
            return $factory->create($container, $args);
        };
    }

    #[Override]
    public function compile(): string
    {
        return ServiceFactoryConverter::convert($this->function)->compile('...');
    }

    #[Override]
    public function type(): ?string
    {
        return Closure::class;
    }

    #[Override]
    public function validate(ContainerBuilder $builder): bool
    {
        $function = ServiceFactoryConverter::convert($this->function);

        return !$function instanceof ValidatableInterface || $function->validate($builder);
    }

    #[Override]
    public function traverse(): Generator
    {
        $function = ServiceFactoryConverter::convert($this->function);
        $hasChange = false;

        if ($function instanceof MethodServiceFactory) {
            $object = yield $function->object;
            assert($object instanceof ValueInterface || $object === null);

            if ($object !== null && $object !== $function->object) {
                $function = new MethodServiceFactory($object, $function->method);
                $hasChange = true;
            }
        }

        return $hasChange ? new self($function) : $this;
    }
}
