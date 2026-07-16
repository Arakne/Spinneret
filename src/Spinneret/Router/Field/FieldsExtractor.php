<?php

namespace Arakne\Spinneret\Router\Field;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionAttribute;
use ReflectionClass;

use function count;
use function var_export;

/**
 * Extract http request fields from request class attributes implementing {@see RequestFieldInterface}.
 * This class is a functor that will extract fields from the request, and will be used as default fields extractor on the router.
 *
 * First, extract all fields from the request attributes added to the request class.
 * If the attribute is not found, it will extract fields from the request body or query string depending on the request method.
 *
 * Then, for each request class field with an attribute, extract the field from the request.
 */
final readonly class FieldsExtractor implements FieldsExtractorInterface
{
    private const string DEFAULT_EXTRACTOR = "\0";

    public function __construct(
        /**
         * The class name of the target request,
         * where fields mapping will be extracted from attributes
         *
         * @var class-string
         */
        private string $requestClassName,
    ) {}

    /**
     * Extract fields from the request
     *
     * @param ServerRequestInterface $request
     * @return array<string, mixed>
     */
    #[Override]
    public function __invoke(ServerRequestInterface $request): array
    {
        $extractors = $this->fieldsExtractors($request->getMethod());

        $fields = $extractors[self::DEFAULT_EXTRACTOR]->extractAll($request);

        foreach ($extractors as $name => $extractor) {
            if ($name !== self::DEFAULT_EXTRACTOR) {
                /** @psalm-suppress MixedAssignment */
                $fields[$name] = $extractor->extract($request, $name);
            }
        }

        return $fields;
    }

    /**
     * Compile the extraction code to PHP closure code
     * The generated callback can be called with a PSR-7 request as argument, and will return an array of extracted fields.
     *
     * Will generates code like:
     * ```php
     * fn ($request) => ['key' => $request->getQueryParams()['key'] ?? null] + (array) ($request->getParsedBody() ?? [])
     * ```
     *
     * @param string $method The HTTP method of the request
     *
     * @return string
     */
    public function compile(string $method): string
    {
        $extractors = $this->fieldsExtractors($method);

        if (count($extractors) === 1) {
            return 'fn ($request) => ' . $extractors[self::DEFAULT_EXTRACTOR]->compileExtractAll('$request');
        }

        $fields = '[';

        foreach ($extractors as $name => $extractor) {
            if ($name === self::DEFAULT_EXTRACTOR) {
                continue;
            }

            $fields .= var_export($name, true) . ' => ' . $extractor->compileExtract('$request', $name).', ';
        }

        $fields .= ']';
        $fields .= ' + ' . $extractors[self::DEFAULT_EXTRACTOR]->compileExtractAll('$request');

        return 'fn ($request) => ' . $fields;
    }

    /**
     * Load all fields extractors from the request class attributes
     *
     * @param string $method The HTTP method of the request
     * @return array{"\0": RequestFieldInterface, ...<string, RequestFieldInterface>}
     */
    private function fieldsExtractors(string $method): array
    {
        // @todo handle HttpField attribute, or allow to define field name on extractor attribute
        $reflection = new ReflectionClass($this->requestClassName);
        $extractors = [];

        foreach ($reflection->getAttributes(RequestFieldInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $extractors[self::DEFAULT_EXTRACTOR] = $attribute->newInstance();
        }

        $extractors[self::DEFAULT_EXTRACTOR] ??= match ($method) {
            'GET', 'HEAD', 'OPTIONS', 'DELETE' => new QueryString(),
            default => new RequestBody(),
        };

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(RequestFieldInterface::class, ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                $extractors[$property->getName()] = $attribute->newInstance();
            }
        }

        /** @var array{"\0": RequestFieldInterface, ...<string, RequestFieldInterface>} */
        return $extractors;
    }
}
