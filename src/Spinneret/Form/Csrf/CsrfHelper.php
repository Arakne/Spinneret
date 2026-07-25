<?php

namespace Arakne\Spinneret\Form\Csrf;

use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use ReflectionClass;
use ReflectionProperty;

use function iterator_to_array;

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
        $fields = [];

        foreach ($this->getCsrfParameters($request, $psrRequest) as $name => $csrf) {
            $token = $csrf->token();

            if ($token !== null) {
                $fields[$name] = $token;
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
     * @param class-string<T> $request The request class name
     * @param ServerRequestInterface $serverRequest The server request, use to extract the CSRF token.
     * @param array<string, mixed> $data Base form data
     *
     * @return FormInterface<T>
     *
     * @template T as object
     */
    public function form(string $request, ServerRequestInterface $serverRequest, array $data = []): FormInterface
    {
        return $this->formFactory->create($request, iterator_to_array($this->getCsrfParameters($request, $serverRequest)) + $data);
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

    /**
     * Try to generate all CSRF tokens fields for the given request class
     *
     * @param class-string $request The request class
     * @param ServerRequestInterface $psrRequest The server request, use to extract the session token.
     *
     * @return iterable<string, CsrfTokenParameters> Map of field name to CSRF token parameters
     */
    public function getCsrfParameters(string $request, ServerRequestInterface $psrRequest): iterable
    {
        $properties = ($this->cache[$request] ??= $this->extractCsrfProperties($request));

        foreach ($properties as $name => $csrf) {
            $token = $csrf->extract($psrRequest, $name);

            if ($token !== null) {
                yield $name => $token;
            }
        }
    }
}
