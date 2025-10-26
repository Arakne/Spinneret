<?php

namespace Arakne\Spinneret\Form\Csrf;

use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Helper for handle form with CSRF token
 */
final class CsrfHelper
{
    /**
     * Cache of CSRF properties by request class
     *
     * The key is the request class.
     * The value is the map of property name to the CSRF attribute.
     *
     * @var array<class-string, array<string, Csrf>>
     */
    private array $cache = [];

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {}

    /**
     * Inject the CSRF token to the request object
     *
     * Note: this method will directly modify the request object
     *
     * @param R $request The request object
     * @param ServerRequestInterface $psrRequest The server request, use to extract the session token.
     *
     * @return R
     * @template R as object The modified request object
     */
    public function setCsrf(object $request, ServerRequestInterface $psrRequest): object
    {
        $properties = ($this->cache[$request::class] ??= $this->extractCsrfProperties($request));

        foreach ($properties as $name => $csrf) {
            $token = $csrf->extract($psrRequest, $name);

            if ($token) {
                $request->$name = $token;
            }
        }

        return $request;
    }

    /**
     * Try to generate the CSRF token for the given request class
     *
     * @param class-string $request The request class
     * @param ServerRequestInterface $psrRequest The server request, use to extract the session token.
     *
     * @return string|null
     */
    public function getCsrfToken(string $request, ServerRequestInterface $psrRequest): ?string
    {
        $properties = ($this->cache[$request] ??= $this->extractCsrfProperties($request));

        foreach ($properties as $name => $csrf) {
            $token = $csrf->extract($psrRequest, $name);

            if ($token) {
                return $token->token();
            }
        }

        return null;
    }

    /**
     * Try to generate all CSRF tokens fields for the given request class
     *
     * @param class-string $request The request class
     * @param ServerRequestInterface $psrRequest The server request, use to extract the session token.
     *
     * @return array<string, string> Map of field name to CSRF token
     */
    public function getCsrfFields(string $request, ServerRequestInterface $psrRequest): array
    {
        $properties = ($this->cache[$request] ??= $this->extractCsrfProperties($request));
        $fields = [];

        foreach ($properties as $name => $csrf) {
            $token = $csrf->extract($psrRequest, $name);

            if ($token) {
                $fields[$name] = $token->token();
            }
        }

        return $fields;
    }

    /**
     * Create the form for the given request class, and import the CSRF token
     *
     * This method allows to render the form view with the CSRF token before the form is submitted.
     *
     * Usage:
     * ```php
     * $form = $helper->form(MyForm::class, $psrRequest);
     * $view = $form->view();
     * $view['csrf']->value; // The CSRF token
     * ```
     *
     * @param class-string<T> $requestClassName The request class name
     * @param ServerRequestInterface $serverRequest The server request, use to extract the CSRF token.
     *
     * @return FormInterface<T>
     *
     * @template T as object
     */
    public function form(string $requestClassName, ServerRequestInterface $serverRequest): FormInterface
    {
        /** @psalm-suppress MixedMethodCall */
        $request = new $requestClassName();

        return $this->formFactory
            ->create($requestClassName)
            ->import($this->setCsrf($request, $serverRequest))
        ;
    }

    /**
     * @param object|class-string $request
     * @return array<string, Csrf>
     */
    private function extractCsrfProperties(object|string $request): array
    {
        $ret = [];
        $r = new ReflectionClass($request);

        foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            foreach ($property->getAttributes(Csrf::class) as $attribute) {
                $ret[$property->name] = $attribute->newInstance();
            }
        }

        return $ret;
    }
}
