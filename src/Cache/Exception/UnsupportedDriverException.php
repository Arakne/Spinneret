<?php

namespace Arakne\Spinneret\Cache\Exception;

use Psr\SimpleCache\CacheException;
use RuntimeException;

use function sprintf;

final class UnsupportedDriverException extends RuntimeException implements CacheException
{
    public function __construct(string $driver, string $cause)
    {
        parent::__construct(sprintf('Unsupported cache driver "%s": %s', $driver, $cause));
    }
}
