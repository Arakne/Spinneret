<?php

namespace Arakne\Spinneret\Router;

use Override;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SfUrlGeneratorInterface;

use function array_key_exists;
use function is_object;

/**
 * Base url generator implementation using Symfony's UrlGeneratorInterface.
 */
final class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly SfUrlGeneratorInterface $sfUrlGenerator,
        private readonly FormFactoryInterface $formFactory,
    ) {}

    #[Override]
    public function url(string|object $request, array $parameters = []): string
    {
        if (is_object($request)) {
            /** @var mixed $value */
            foreach ($this->extractRequestData($request) as $name => $value) {
                if ($value !== null && !array_key_exists($name, $parameters)) {
                    $parameters[$name] = $value;
                }
            }

            $request = $request::class;
        }

        return $this->sfUrlGenerator->generate($request, $parameters, SfUrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Extract request parameters as array
     *
     * @todo Use router information about properties to extract or not
     *
     * @param object $request
     * @return array<string, mixed>
     */
    private function extractRequestData(object $request): array
    {
        // @todo filter body/attribute/header parameters
        return $this->formFactory->import($request)->httpValue();
    }
}
