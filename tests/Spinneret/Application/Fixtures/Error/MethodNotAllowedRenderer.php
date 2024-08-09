<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Error;

use Arakne\Spinneret\Router\Result\MethodNotAllowed;
use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Override;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractViewRenderer<MethodNotAllowed>
 * @implements ResponseConfiguratorInterface<MethodNotAllowed>
 */
final class MethodNotAllowedRenderer extends AbstractViewRenderer implements ResponseConfiguratorInterface
{
    public function __invoke(View $view, MethodNotAllowed $data): void
    {
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Method not allowed</title>
    </head>
    <body>
        <h1>Error 405</h1>
        <p>Method not allowed. Allow <?= implode(', ', $data->allowedMethods) ?>.</p>
    </body>
</html>
<?php
    }

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withStatus(405)
            ->withHeader('Allow', $data->allowedMethods)
        ;
    }
}
