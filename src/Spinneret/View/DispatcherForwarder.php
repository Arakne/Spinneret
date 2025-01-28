<?php

namespace Arakne\Spinneret\View;

use Arakne\Spinneret\Presenter\PresenterDispatcherInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use LogicException;
use Override;

/**
 * Default forwarder implementation using the presenter dispatcher.
 *
 * @see PresenterDispatcherInterface For more information about the dispatcher.
 */
final readonly class DispatcherForwarder implements ForwarderInterface
{
    public function __construct(
        private PresenterDispatcherInterface $dispatcher,
        private ViewEngineInterface $engine,
    ) {}

    #[Override]
    public function display(object $request, View $view): void
    {
        $this->engine->display($this->forward($request, $view), $view);
    }

    #[Override]
    public function render(object $request, View $view): string
    {
        return $this->engine->render($this->forward($request, $view), $view);
    }

    private function forward(object $request, View $view): object
    {
        $psrRequest = $view->psrRequest ?? throw new LogicException('A PSR request is required to forward the request');
        $routedRequest = new RoutedRequest($psrRequest, $request);

        return $this->dispatcher->dispatch($routedRequest);
    }
}
