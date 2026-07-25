<?php

namespace Arakne\Tests\Spinneret\Runner\Fixtures;

use Arakne\Spinneret\Runner\RequestProviderExceptionInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class AccessDeniedException extends RuntimeException implements RequestProviderExceptionInterface
{
    #[Override]
    public function toRequest(object $baseRequest, ServerRequestInterface $psrRequest): object
    {
        return new AccessDenied($this->getMessage());
    }
}
