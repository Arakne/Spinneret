<?php

namespace Arakne\Spinneret\Router\Field;

use Attribute;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function sprintf;
use function var_export;

/**
 * Define the target as filled with the query string (e.g. GET data)
 *
 * When set to a form field, the field will be filled with the query string.
 * When set to the class, by default all fields will be filled with the query string.
 *
 * The attribute on the property will override the attribute on the class.
 *
 * @see RequestBody for request body parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final readonly class QueryString implements RequestFieldInterface
{
    #[Override]
    public function extract(ServerRequestInterface $request, string $name): mixed
    {
        return $request->getQueryParams()[$name] ?? null;
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        // Not actually the case (keys may be int), but adding a check is costly for nothing
        /** @var array<string, mixed> */
        return $request->getQueryParams();
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return sprintf('%s->getQueryParams()[%s] ?? null', $requestVarName, var_export($name, true));
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        return sprintf('%s->getQueryParams()', $requestVarName);
    }
}
