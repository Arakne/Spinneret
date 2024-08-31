<?php

namespace Arakne\Spinneret\Form\Csrf;

use Arakne\Spinneret\Router\Field\RequestFieldInterface;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Attribute;
use BadMethodCallException;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\RegistryInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintInterface;
use Quatrevieux\Form\Validator\Constraint\ConstraintValidatorInterface;

use Quatrevieux\Form\Validator\FieldError;

use Quatrevieux\Form\View\FieldView;
use Quatrevieux\Form\View\FormView;
use Quatrevieux\Form\View\Provider\FieldViewProviderConfigurationInterface;

use Quatrevieux\Form\View\Provider\FieldViewProviderInterface;

use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Csrf implements RequestFieldInterface, ConstraintInterface, ConstraintValidatorInterface, FieldViewProviderConfigurationInterface, FieldViewProviderInterface
{
    public const string CODE = '642ecf60-c56e-547b-9064-dd30d553f5dd';

    public function __construct(
        private string $key,
    ) {
    }

    #[Override]
    public function extract(ServerRequestInterface $request, string $name): ?CsrfTokenParameters
    {
        return self::extractImpl($request, $name, $this->key);
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        throw new BadMethodCallException('Csrf must be used on a single property');
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return self::class . '::extractImpl(' . $requestVarName . ', ' . var_export($name, true) . ', ' . var_export($this->key, true) . ')';
    }

    #[Override]
    public function compileExtractAll(string $requestVarName): string
    {
        throw new BadMethodCallException('Csrf must be used on a single property');
    }

    #[Override]
    public function validate(ConstraintInterface $constraint, mixed $value, object $data): ?FieldError
    {
        if (!$value instanceof CsrfTokenParameters || !$value->validate()) {
            return new FieldError(
                message: 'Invalid CSRF token',
                code: self::CODE, // @todo message parameter
            );
        }

        return null;
    }

    #[Override]
    public function getValidator(RegistryInterface $registry): ConstraintValidatorInterface
    {
        return $this;
    }

    #[Override]
    public function getViewProvider(RegistryInterface $registry): FieldViewProviderInterface
    {
        return $this;
    }

    #[Override]
    public function view(FieldViewProviderConfigurationInterface $configuration, string $name, mixed $value, array|FieldError|null $error, array $attributes): FieldView|FormView
    {
        $attributes['type'] ??= 'hidden';

        return new FieldView(
            $name,
            $value instanceof CsrfTokenParameters ? $value->token() : null, // Always generate the actual token
            $error instanceof FieldError ? $error : null,
            $attributes,
        );
    }

    public static function extractImpl(ServerRequestInterface $request, string $name, string $key): ?CsrfTokenParameters
    {
        $session = $request->getAttribute(ParsedCookie::class);
        $input = ((array) $request->getParsedBody())[$name] ?? null;

        if (!$session instanceof ParsedCookie || ($input !== null && !is_string($input))) {
            return null;
        }

        return new CsrfTokenParameters(
            key: $key,
            secret: $session->token,
            input: $input,
        );
    }
}
