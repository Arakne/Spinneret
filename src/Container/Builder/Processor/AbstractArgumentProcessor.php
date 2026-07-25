<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;
use Arakne\Spinneret\Container\Value\DynamicArray;
use Arakne\Spinneret\Container\Value\Literal;
use Arakne\Spinneret\Container\Value\NestedValueInterface;
use Arakne\Spinneret\Container\Value\ValueInterface;
use Override;

use function is_array;

/**
 * Base process for processing service arguments recursively.
 */
abstract readonly class AbstractArgumentProcessor implements ContainerBuilderProcessorInterface
{
    #[Override]
    final public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->services as $service) {
            /** @var mixed $argument */
            foreach ($service->arguments as $index => $argument) {
                $service->arguments[$index] = $this->processArgument($builder, $argument);
            }
        }
    }

    /**
     * Process a single value
     * If the value is a {@see NestedValueInterface}, all its nested values are processed before calling this method.
     *
     * @param ContainerBuilder $builder The current container builder.
     * @param mixed $value Value to process.
     *
     * @return mixed The new value.
     */
    abstract protected function processValue(ContainerBuilder $builder, mixed $value): mixed;

    private function processArgument(ContainerBuilder $builder, mixed $argument): mixed
    {
        if (is_array($argument)) {
            $argument = new DynamicArray($argument);
        }

        if ($argument instanceof NestedValueInterface) {
            $argument = $this->processNestedValue($builder, $argument);
        }

        return $this->processValue($builder, $argument);
    }

    private function processNestedValue(ContainerBuilder $builder, NestedValueInterface $value): ValueInterface
    {
        $generator = $value->traverse();

        while ($generator->valid()) {
            $current = $generator->current();
            /** @var mixed $proceed */
            $proceed = $this->processArgument($builder, $current);

            if (!$proceed instanceof ValueInterface) {
                $proceed = new Literal($value);
            }

            $generator->send($proceed);
        }

        return $generator->getReturn();
    }
}
