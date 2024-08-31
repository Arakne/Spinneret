<?php

namespace Arakne\Spinneret\Form\Csrf;

use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\FormFactoryInterface;
use Quatrevieux\Form\FormInterface;
use ReflectionClass;
use ReflectionProperty;

final readonly class CsrfHelper
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function setCsrf(object $request, ServerRequestInterface $psrRequest): object
    {
        // @todo cache ou génération de code ?
        $r = new ReflectionClass($request);

        foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            foreach ($property->getAttributes(Csrf::class) as $attribute) {
                $csrf = $attribute->newInstance()->extract($psrRequest, $property->getName());

                if ($csrf) {
                    $property->setValue($request, $csrf);
                }
            }
        }

        return $request;
    }

    /**
     * @param class-string<T> $requestClassName
     * @param ServerRequestInterface $serverRequest
     * @return FormInterface<T>
     *
     * @template T as object
     */
    public function form(string $requestClassName, ServerRequestInterface $serverRequest): FormInterface
    {
        return $this->formFactory
            ->create($requestClassName)
            ->import($this->setCsrf(new $requestClassName(), $serverRequest))
        ;
    }
}
