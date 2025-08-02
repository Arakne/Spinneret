<?php

namespace Arakne\Spinneret\Container\Value;

use Arakne\Spinneret\Container\Service\ServiceFactoryConverter;
use Arakne\Spinneret\Container\Service\ServiceFactoryInterface;
use Attribute;
use Closure;
use Override;
use Psr\Container\ContainerInterface;

use function array_is_list;
use function assert;
use function implode;
use function is_array;

/**
 * Resolves the value by calling a function or method with the given arguments.
 * This type is equivalent to an inlined service created by a factory.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Call implements ValueInterface
{
    use ValueHelperTrait;

    public function __construct(
        /**
         * The function or method to call.
         * If the value is not a {@see ServiceFactoryInterface}, it will be converted to one using {@see ServiceFactoryConverter}.
         *
         * @var ServiceFactoryInterface|Closure|callable-string
         */
        public ServiceFactoryInterface|Closure|string $function,

        /**
         * List of arguments to pass to the function or method.
         * If an argument is a {@see ValueInterface}, it will be resolved by the container.
         *
         * @var list<mixed>
         */
        public array $arguments = [],
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): mixed
    {
        $arguments = new DynamicArray($this->arguments)->resolve($container);
        assert(array_is_list($arguments));

        return ServiceFactoryConverter::convert($this->function)->create($container, $arguments);
    }

    #[Override]
    public function compile(): string
    {
        $arguments = [];

        /** @var mixed $argument */
        foreach ($this->arguments as $argument) {
            if (is_array($argument)) {
                $argument = new DynamicArray($argument);
            }

            if ($argument instanceof ValueInterface) {
                $argument = $argument->compile();
            } else {
                $argument = Literal::dump($argument);
            }

            $arguments[] = $argument;
        }

        return ServiceFactoryConverter::convert($this->function)->compile(implode(', ', $arguments));
    }

    #[Override]
    public function type(): ?string
    {
        return null;
    }
}
