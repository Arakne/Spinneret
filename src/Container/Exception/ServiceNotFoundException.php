<?php

namespace Arakne\Spinneret\Container\Exception;

use OutOfBoundsException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * The service or alias was not found in the container.
 *
 * @api
 */
class ServiceNotFoundException extends OutOfBoundsException implements NotFoundExceptionInterface, SpinneretContainerExceptionInterface {}
