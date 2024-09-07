<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\Login;

use Arakne\Spinneret\Presenter\PresenterInterface;
use Arakne\Spinneret\Router\RoutedRequest;
use Arakne\Tests\Spinneret\Security\Fixtures\TestUser;
use Override;

/**
 * @implements PresenterInterface<LoginRequest>
 */
class LoginPresenter implements PresenterInterface
{
    #[Override]
    public function handleSuccess(object $request, RoutedRequest $routedRequest): object
    {
        return new LoginResponse(new TestUser(
            $request->username,
            $request->password,
        ));
    }

    #[Override]
    public function handleError(object $request, RoutedRequest $routedRequest): object
    {
        throw new \BadMethodCallException();
    }
}
