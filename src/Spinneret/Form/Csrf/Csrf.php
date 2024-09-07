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

/**
 * Enable CSRF protection on a form
 *
 * The attribute must be used on a property.
 * The property mut be of type `CsrfTokenParameters` or mixed.
 * The value of the property is opaque and should not be modified nor used directly.
 *
 * The CSRF field will be filled by the field extractor on the router stage.
 * The field is always extracted from the request body (i.e. POST parameters).
 *
 * Note: This CSRF system is entirely stateless and does not store any token on the server or on the client.
 *       The token is generated from the session id and the form key. So, to work properly, the session cookie must be
 *       parsed and available in the request attributes, and parameters must be extracted from the request.
 *
 * Usage:
 * ```php
 * // The form class
 * class MyForm
 * {
 *     // Define fields...
 *
 *     #[Csrf(self::class)] // Use the class name as key
 *     public CsrfTokenParameters $csrf;
 * }
 *
 * // Generate the view with the CSRF token
 * public function view(CsrfHelper $helper, ServerRequestInterface $serverRequest): FormView
 * {
 *     $form = $helper->form(MyForm::class, $request);
 *
 *     return $form->view();
 * }
 * ```
 *
 * @implements ConstraintValidatorInterface<Csrf>
 * @implements FieldViewProviderInterface<Csrf>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Csrf implements RequestFieldInterface, ConstraintInterface, ConstraintValidatorInterface, FieldViewProviderConfigurationInterface, FieldViewProviderInterface
{
    public const string CODE = '642ecf60-c56e-547b-9064-dd30d553f5dd';

    public function __construct(
        /**
         * The CSRF key. Will be used to generate the token.
         *
         * The key should be unique for each form.
         * You can use the form class name as key.
         */
        private string $key,

        /**
         * The error message to display when the token is invalid or missing.
         */
        private string $message = 'Invalid CSRF token',

        /**
         * The attribute name where the parsed cookie is stored.
         *
         * The parsed cookie contains the session id which is used to generate the token.
         * If the attribute is not found or the attribute is not an instance of `ParsedCookie`, the CSRF token will be invalid.
         */
        private string $parsedCookieAttribute = ParsedCookie::class,
    ) {
    }

    #[Override]
    public function extract(ServerRequestInterface $request, string $name): ?CsrfTokenParameters
    {
        return self::extractImpl($request, $name, $this->key, $this->parsedCookieAttribute);
    }

    #[Override]
    public function extractAll(ServerRequestInterface $request): array
    {
        throw new BadMethodCallException('Csrf must be used on a single property');
    }

    #[Override]
    public function compileExtract(string $requestVarName, string $name): string
    {
        return self::class . '::extractImpl(' . $requestVarName . ', ' . var_export($name, true) . ', ' . var_export($this->key, true) . ', ' . var_export($this->parsedCookieAttribute, true) . ')';
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
                message: $this->message,
                code: self::CODE,
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
    public function view(FieldViewProviderConfigurationInterface $configuration, string $name, mixed $value, array|FieldError|null $error, array $attributes): FieldView
    {
        $attributes['type'] ??= 'hidden';

        return new FieldView(
            $name,
            $value instanceof CsrfTokenParameters ? $value->token() : null, // Always generate the actual token
            $error instanceof FieldError ? $error : null,
            $attributes,
        );
    }

    /**
     * @internal
     */
    public static function extractImpl(ServerRequestInterface $request, string $name, string $key, string $parsedCookieAttribute): ?CsrfTokenParameters
    {
        $session = $request->getAttribute($parsedCookieAttribute);
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
