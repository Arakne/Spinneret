<?php

namespace Arakne\Spinneret\Container\Exception;

use LogicException;

/**
 * The argument is missing and cannot be resolved.
 *
 * @api
 */
class MissingArgumentException extends LogicException implements SpinneretContainerExceptionInterface {}
