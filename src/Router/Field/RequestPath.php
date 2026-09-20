<?php

namespace Arakne\Spinneret\Router\Field;

use Attribute;
use BadMethodCallException;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function sprintf;
use function var_export;

/**
 * Define the target as filled with the path parameters (extracted from {@see ServerRequestInterface::getAttribute()})
 *
 * This attribute can only target a property, and never the entire class.
 *
 * Note: This attribute is same as {@see RequestAttribute} except it will be exported to generate the URL.
 *
 * @see RequestBody for request body parameters
 * @see QueryString for request query string parameters
 * @see RequestAttribute for attribute parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class RequestPath implements RequestFieldInterface
{
    public function __construct(
        /**
         * The name of the attribute to extract
         *
         * If null, the name of the property will be used.
         * Defining this value when using the attribute on the class will have no effect.
         */
        private ?string $name = null,
    ) {}

    #[Override]
    public function extract(ServerRequestInterface $request, string $name): mixed
    {
        return $request->getAttribute($this->name ?? $name);
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        throw new BadMethodCallException('RequestPath only supports single property');
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return sprintf('%s->getAttribute(%s)', $requestVarName, var_export($this->name ?? $name, true));
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        throw new BadMethodCallException('RequestPath only supports single property');
    }

    #[Override]
    public function isUrl(): bool
    {
        return true;
    }
}
