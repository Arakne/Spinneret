<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Router\Attribute\Get;
use Arakne\Spinneret\Router\Field\RequestFieldInterface;
use Override;
use Quatrevieux\Form\FormFactoryInterface;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SfUrlGeneratorInterface;

use function array_intersect_key;
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

        /**
         * Map of request class name to exported fields.
         * Exported fields will be defined as array keys.
         *
         * Must not be set manually: this parameter should only be used by the url generator compiler.
         *
         * @var array<class-string, array<string, true>>
         */
        private array $exportedFieldsCache = [],
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
     * @param object $request
     * @return array<string, mixed>
     */
    private function extractRequestData(object $request): array
    {
        $data = $this->formFactory->import($request)->httpValue();

        return array_intersect_key($data, $this->exportedFields($request));
    }

    /**
     * @param object $request
     * @return array<string, true>
     */
    private function exportedFields(object $request): array
    {
        return $this->exportedFieldsCache[$request::class] ??= self::computedExportedFields($request::class);
    }

    /**
     * @param class-string $request
     * @param bool|null $isGet Does the current request is for a get route? Set to null to deduce it from the class.
     * @return array<string, true>
     * @internal
     */
    public static function computedExportedFields(string $request, ?bool $isGet = null): array
    {
        $class = new ReflectionClass($request);

        if ($isGet === null) {
            foreach ($class->getAttributes(RequestFieldInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $isGet = $attribute->newInstance()->isUrl();
            }

            if ($isGet === null) {
                $isGet = $class->getAttributes(Get::class) !== [];
            }

        }

        $exportedFields = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $isExported = null;

            foreach ($property->getAttributes(RequestFieldInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $isExported = $attribute->newInstance()->isUrl();
            }

            $isExported ??= $isGet;

            if ($isExported) {
                $exportedFields[$property->name] = true;
            }
        }

        return $exportedFields;
    }
}
