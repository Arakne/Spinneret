<?php

namespace Arakne\Spinneret\Router\Field;

use Attribute;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function sprintf;
use function var_export;

/**
 * Define the target as filled with the the server request attribute ({@see ServerRequestInterface::getAttribute()})
 *
 * When set to a form field, the field will be filled with the attribute.
 * When set to the class, by default all fields will be filled with all attributes.
 *
 * The attribute on the property will override the attribute on the class.
 *
 * @see RequestBody for request body parameters
 * @see QueryString for request query string parameters
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final readonly class RequestAttribute implements RequestFieldInterface
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
        // Not actually the case (keys may be int), but adding a check is costly for nothing
        /** @var array<string, mixed> */
        return $request->getAttributes();
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return sprintf('%s->getAttribute(%s)', $requestVarName, var_export($this->name ?? $name, true));
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        return sprintf('%s->getAttributes()', $requestVarName);
    }
}
