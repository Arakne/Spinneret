<?php

namespace Arakne\Spinneret\Container\Argument;

use Closure;
use Override;
use Psr\Container\ContainerInterface;

use function sprintf;

// @todo test + doc
final readonly class ClosureArgument implements ArgumentInterface
{
    public function __construct(
        private ArgumentInterface $argument,
    ) {}

    #[Override]
    public function resolve(ContainerInterface $container): Closure
    {
        return fn () => $this->argument->resolve($container);
    }

    #[Override]
    public function compile(): string
    {
        return sprintf('(fn () => %s)', $this->argument->compile());
    }

    #[Override]
    public function type(): ?string
    {
        return Closure::class;
    }
}
