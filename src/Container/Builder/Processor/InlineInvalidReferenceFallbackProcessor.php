<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\SpinneretContainerInterface;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\Reference;
use Override;
use Psr\Container\ContainerInterface;

/**
 * Inline the fallback value of an invalid reference.
 */
final readonly class InlineInvalidReferenceFallbackProcessor extends AbstractArgumentProcessor
{
    #[Override]
    protected function processValue(ContainerBuilder $builder, mixed $value): mixed
    {
        if (
            !$value instanceof Reference
            || ($value->defaultValueOnInvalid === null && !$value->nullOnInvalid)
            || $value->id === ContainerInterface::class
            || $value->id === SpinneretContainerInterface::class
        ) {
            return $value;
        }

        $service = $builder->find($value->id);

        if ($service?->validate($builder) === true) {
            return $value;
        }

        if ($value->defaultValueOnInvalid !== null) {
            return $value->defaultValueOnInvalid;
        }

        return new Literal(null);
    }
}
