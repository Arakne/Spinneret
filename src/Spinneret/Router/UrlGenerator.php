<?php

namespace Arakne\Spinneret\Router;

use Override;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SfUrlGeneratorInterface;

use function array_key_exists;
use function get_object_vars;
use function is_object;

/**
 * Base url generator implementation using Symfony's UrlGeneratorInterface.
 */
final readonly class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private SfUrlGeneratorInterface $sfUrlGenerator,
    ) {}

    #[Override]
    public function url(string|object $request, array $parameters = []): string
    {
        if (is_object($request)) {
            /** @var mixed $value */
            foreach (get_object_vars($request) as $name => $value) {
                if ($value !== null && !array_key_exists($name, $parameters)) {
                    $parameters[$name] = $value;
                }
            }

            $request = $request::class;
        }

        return $this->sfUrlGenerator->generate($request, $parameters, SfUrlGeneratorInterface::ABSOLUTE_URL);
    }
}
