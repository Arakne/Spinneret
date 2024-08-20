<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Configurable;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use BadMethodCallException;
use Override;

final readonly class ShowConfigPresenter implements PresenterInterface
{
    public function __construct(
        private TestConfig $config,
    ) {
    }

    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return new ShowConfigResponse(
            $this->config->message,
            $this->config->computed,
        );
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        throw new BadMethodCallException();
    }
}
