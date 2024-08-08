<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Result\NotFound;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

use function str_starts_with;

/**
 * Default implementation of the router
 *
 * Internally use Symfony router to resolve the request class,
 * and vincent4vx/form to fill and validate the request.
 *
 * Note: configured routes must have as attributes:
 * - _target: The request class name to instantiate
 * - _fields_extractor: The class name used to extract fields from the request. Will be instantiated with the target class name as argument.
 */
final readonly class Router implements RouterInterface
{
    public function __construct(
        private UrlMatcherInterface $matcher,
        private FormFactoryInterface $formFactory,
    ) {
    }

    #[Override]
    public function request(ServerRequestInterface $request): RoutedRequest
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Matcher mutates its state, so we need to clone it
        $matcher = clone $this->matcher;

        $currentContext = $matcher->getContext();

        // Change the current HTTP method
        if ($currentContext->getMethod() !== $method) {
            $currentContext = clone $currentContext;
            $currentContext->setMethod($method);

            $matcher->setContext($currentContext);
        }

        try {
            $attributes = $matcher->match($path);
        } catch (ResourceNotFoundException) {
            return new RoutedRequest($request, new NotFound(), false, null);
        } catch (MethodNotAllowedException $e) {
            return new RoutedRequest($request, new MethodNotAllowed($method, $e->getAllowedMethods()), false, null);
        }

        // Set attributes as request attributes, except those starting with _
        foreach ($attributes as $key => $value) {
            if (!str_starts_with($key, '_')) {
                $request = $request->withAttribute($key, $value);
            }
        }

        // @todo check attributes
        // @todo optimisation: field extractor vide et request en singleton
        $form = $this->formFactory->create($attributes['_target']);
        $fieldsExtractor = new $attributes['_fields_extractor']($attributes['_target']);

        $submitted = $form->submit($fieldsExtractor($request));

        return new RoutedRequest($request, $submitted->value(), $submitted->valid(), $submitted);
    }
}
