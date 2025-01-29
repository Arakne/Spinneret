<?php

namespace Arakne\Spinneret\Router;

use Arakne\Spinneret\Router\Field\FieldsExtractorInterface;
use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\Router\Result\NotFound;
use LogicException;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Quatrevieux\Form\FormFactoryInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

use function array_values;
use function is_callable;
use function sprintf;
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
    ) {}

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
            /** @var array<string, scalar> $attributes */
            $attributes = $matcher->match($path);
        } catch (ResourceNotFoundException) {
            return new RoutedRequest($request, new NotFound(), false, null);
        } catch (MethodNotAllowedException $e) {
            return new RoutedRequest($request, new MethodNotAllowed($method, array_values($e->getAllowedMethods())), false, null);
        }

        /** @var class-string $target */
        $target = $attributes['_target'] ?? throw new LogicException('Route must have a _target attribute');
        /** @var class-string<FieldsExtractorInterface> $fieldsExtractorClassName */
        $fieldsExtractorClassName = $attributes['_fields_extractor'] ?? throw new LogicException('Route must have a _fields_extractor attribute');

        // Set attributes as request attributes, except those starting with _
        foreach ($attributes as $key => $value) {
            if (!str_starts_with($key, '_')) {
                $request = $request->withAttribute($key, $value);
            }
        }

        // @todo valider les droits
        // foreach ($this->checkers as $checker) {
        //     $result = $checker->check($request, $target, $attributes);
        //     if ($result !== null) {
        //         return new RoutedRequest($request, $result, false, null);
        //     }
        // }

        // @todo optimisation: field extractor vide et request en singleton
        $form = $this->formFactory->create($target);

        /** @var callable(RequestInterface):array<string, mixed> $fieldsExtractor */
        $fieldsExtractor = new $fieldsExtractorClassName($target);

        if (!is_callable($fieldsExtractor)) {
            throw new LogicException(sprintf('Fields extractor for route %s must be a callable', $target));
        }

        $submitted = $form->submit($fieldsExtractor($request));
        $requestDto = $submitted->value();
        $request = $request->withAttribute('request', $requestDto); // @todo constant ?

        return new RoutedRequest($request, $requestDto, $submitted->valid(), $submitted);
    }
}
