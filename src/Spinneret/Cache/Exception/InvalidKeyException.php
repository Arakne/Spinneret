<?php

namespace Arakne\Spinneret\Cache\Exception;

use InvalidArgumentException;

use function sprintf;

final class InvalidKeyException extends InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid cache key "%s".', $key));
    }
}
