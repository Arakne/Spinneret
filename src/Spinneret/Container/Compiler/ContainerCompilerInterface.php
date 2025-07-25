<?php

namespace Arakne\Spinneret\Container\Compiler;

use Arakne\Spinneret\Container\BuiltContainer;

/**
 * @template R as mixed
 */
interface ContainerCompilerInterface
{
    /**
     * @param BuiltContainer $container
     * @return R
     */
    public function compile(BuiltContainer $container): mixed;
}
