<?php

namespace Arakne\Spinneret\Router\Field;

use Attribute;
use BadMethodCallException;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Get the value from the request headers.
 *
 * This attributes cannot be used on a class, and must be used on a property.
 * Unlike other field types, if the value is not present, an empty string is returned instead of null.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class RequestHeader implements RequestFieldInterface
{
    public function __construct(
        /**
         * The header name to extract.
         * This value is case-insensitive.
         *
         * If not provided, the property name is used.
         */
        private ?string $name = null,
    ) {}

    #[Override]
    public function extract(ServerRequestInterface $request, string $name): string
    {
        return $request->getHeaderLine($this->name ?? $name);
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        throw new BadMethodCallException('Cannot extract all headers');
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        // @todo use getHeader(xxx)[0] instead of getHeaderLine
        return sprintf('%s->getHeaderLine(%s)', $requestVarName, var_export($this->name ?? $name, true));
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        throw new BadMethodCallException('Cannot extract all headers');
    }
}
