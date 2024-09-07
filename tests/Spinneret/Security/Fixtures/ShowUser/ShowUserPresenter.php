<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\ShowUser;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Spinneret\Security\Serializer\ParsedCookie;
use Override;

/**
 * @implements PresenterInterface<ShowUserRequest>
 */
class ShowUserPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return new ShowUserResponse($request->user);
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        return new NotLoggedResponse($routedRequest->psrRequest->getAttribute(ParsedCookie::class));
    }
}
