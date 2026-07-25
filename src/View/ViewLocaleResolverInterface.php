<?php

namespace Arakne\Spinneret\View;

use Psr\Http\Message\ServerRequestInterface;

interface ViewLocaleResolverInterface
{
    public function resolve(object $data, ?ServerRequestInterface $request): ?string;
}
