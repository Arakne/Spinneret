<?php

namespace Arakne\Spinneret\Container\Builder\Processor;

use Arakne\Spinneret\Container\Builder\ContainerBuilder;

interface ContainerBuilderProcessorInterface
{
    public function process(ContainerBuilder $builder): void;
}
