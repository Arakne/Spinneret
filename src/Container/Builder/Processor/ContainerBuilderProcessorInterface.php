<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;

/**
 * Process services during building of the container.
 *
 * @see ContainerBuilder::build()
 */
interface ContainerBuilderProcessorInterface
{
    /**
     * Default step for processing the container builder.
     */
    public const int STEP_PROCESS = 0;

    /**
     * Final step for processing the container builder.
     */
    public const int STEP_FINALIZE = 1;

    /**
     * Apply the processor to the container builder.
     *
     * @param ContainerBuilder $builder
     * @return void
     */
    public function process(ContainerBuilder $builder): void;
}
