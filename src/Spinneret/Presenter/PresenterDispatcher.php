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
    ) {
    }

    #[Override]
    public function dispatch(RoutedRequest $routedRequest): object
    {
        $presenter = $this->presenters[$routedRequest->routedRequest::class] ?? null;

        if ($presenter === null) {
            throw new LogicException('No presenter found for ' . $routedRequest->routedRequest::class);
        }

        $presenter = $this->container->get($presenter);

        if ($routedRequest->success) {
            return $presenter->handleSuccess($routedRequest->routedRequest, $routedRequest);
        }

        return $presenter->handleError($routedRequest->routedRequest, $routedRequest);
    }
}
