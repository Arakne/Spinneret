<?php

namespace Arakne\Spinneret\Presenter;

use Arakne\Spinneret\Router\RoutedRequest;
use LogicException;
use Override;
use Psr\Container\ContainerInterface;

/**
 * Default presenter dispatcher.
 *
 * Resolve presenter using a map of request class to presenter class,
 * and instantiate the presenter using the PSR-11 container.
 */
final readonly class PresenterDispatcher implements PresenterDispatcherInterface
{
    public function __construct(
        /**
         * The container to use to instantiate presenters.
         * Presenters must be registered using the class name as the service ID.
         */
        private ContainerInterface $container,

        /**
         * Map of request class to presenter class.
         *
         * @var array<class-string, class-string<PresenterInterface>>
         */
        private array $presenters,
    ) {}

    #[Override]
    public function dispatch(RoutedRequest $routedRequest): object
    {
        $presenterClassName = $this->presenters[$routedRequest->routedRequest::class] ?? null;

        if ($presenterClassName === null) {
            throw new LogicException('No presenter found for ' . $routedRequest->routedRequest::class);
        }

        /** @var PresenterInterface $presenter */
        $presenter = $this->container->get($presenterClassName);

        if ($routedRequest->success) {
            return $presenter->handleSuccess($routedRequest->routedRequest, $routedRequest);
        }

        return $presenter->handleError($routedRequest->routedRequest, $routedRequest);
    }
}
