<?php

namespace Arakne\Spinneret\Router;

use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SfUrlGeneratorInterface;

final readonly class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private SfUrlGeneratorInterface $sfUrlGenerator,
    ) {
    }

    #[Override]
    public function url(string $requestClass, array $parameters = []): string
    {
        return $this->sfUrlGenerator->generate($requestClass, $parameters, SfUrlGeneratorInterface::ABSOLUTE_URL);
    }
}
