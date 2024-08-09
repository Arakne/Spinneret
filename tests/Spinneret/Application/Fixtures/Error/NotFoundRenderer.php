<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Error;

use Arakne\Spinneret\Router\Result\NotFound;
use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Override;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractViewRenderer<NotFound>
 */
final class NotFoundRenderer extends AbstractViewRenderer implements ResponseConfiguratorInterface
{
    public function __invoke(View $view, NotFound $data): void
    {
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Not found</title>
    </head>
    <body>
        <h1>Error 404</h1>
        <p><?= htmlentities($data->message ?? 'This page cannot be found') ?></p>
    </body>
</html>
<?php
    }

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(404);
    }
}
