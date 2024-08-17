<?php

namespace Arakne\Spinneret\Router\Field;

use Attribute;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Define the target as filled with the request body (e.g. POST data)
 *
 * When set to a form field, the field will be filled with the request body.
 * When set to the class, by default all fields will be filled with the request body.
 *
 * The attribute on the property will override the attribute on the class.
 *
 * @see QueryString for query string parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final readonly class RequestBody implements RequestFieldInterface
{
    #[Override]
    public function extract(ServerRequestInterface $request, string $name): mixed
    {
        return ((array) $request->getParsedBody())[$name] ?? null;
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        // Not actually the case (keys may be int), but adding a check is costly for nothing
        /** @var array<string, mixed> */
        return (array) ($request->getParsedBody() ?? []);
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return sprintf('((array) %s->getParsedBody())[%s] ?? null', $requestVarName, var_export($name, true));
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        return sprintf('(array) (%s->getParsedBody() ?? [])', $requestVarName);
    }
}
