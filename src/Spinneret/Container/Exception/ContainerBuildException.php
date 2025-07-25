<?php

namespace Arakne\Spinneret\Container\Exception;

use LogicException;

/**
 * The container could not be built due to a configuration error.
 *
 * @api
 */
class ContainerBuildException extends LogicException implements SpinneretContainerExceptionInterface
{

}
