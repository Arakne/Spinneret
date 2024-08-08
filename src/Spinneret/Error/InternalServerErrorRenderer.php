<?php

namespace Arakne\Spinneret\Error;

use Arakne\Spinneret\Application\Application;
use Arakne\Spinneret\Runner\InternalServerError;
use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\ResponseConfiguratorInterface;
use Arakne\Spinneret\View\View;
use Override;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractViewRenderer<InternalServerError>
 * @implements ResponseConfiguratorInterface<InternalServerError>
 */
final class InternalServerErrorRenderer extends AbstractViewRenderer implements ResponseConfiguratorInterface
{
    public function __construct(
        private readonly Application $application,
    ) {
    }

    public function __invoke(View $view, InternalServerError $data): void
    {
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Internal Server Error</title>
    </head>
    <body>
        <h1>Error 500</h1>
        <?php if ($this->application->isDev): ?>
            <p><?= $data->error->getMessage() ?></p>
            <pre><?= $data->error->getTraceAsString() ?></pre>
            <p>During stage <?= $data->step->name ?></p>

            <?php if ($data->request): ?>
                <h2>Parsed request</h2>
                <pre><?php var_dump($data->request) ?></pre>
            <?php endif; ?>
        <?php else: ?>
            <p>Something went wrong</p>
        <?php endif; ?>
    </body>
</html>
<?php
    }

    #[Override]
    public function configureResponse(View $view, object $data, ResponseInterface $response): ResponseInterface
    {
        return $response->withStatus(500);
    }
}
