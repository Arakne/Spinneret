<?php

namespace Arakne\Tests\Spinneret\Security\Fixtures\Login;

use Arakne\Spinneret\Security\AuthenticationCookieHelper;
use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;

/**
 * @implements ViewRendererInterface<LoginResponse>
 * @implements ResponseConfiguratorInterface<LoginResponse>
 */
readonly class LoginRenderer implements ViewRendererInterface, ResponseConfiguratorInterface
{
    public function __construct(
        private AuthenticationCookieHelper $cookieHelper,
    ) {
    }

    #[Override]
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        return json_encode($data);
    }

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $this->cookieHelper->writeResponse($response, $data->authenticatedUser);
    }
}
