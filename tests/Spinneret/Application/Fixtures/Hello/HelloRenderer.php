<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Hello;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewRendererInterface;
use Override;
use function htmlentities;

/**
 * @implements ViewRendererInterface<HelloResponse>
 */
final class HelloRenderer implements ViewRendererInterface
{
    #[Override]
    public function display(View $view, object $data): void
    {
        echo $this->render($view, $data);
    }

    #[Override]
    public function render(View $view, object $data): string
    {
        $name = htmlentities($data->name, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Hello</title>
    </head>
    <body>
        <h1>Hello, {$name}!</h1>
    </body>
</html>
HTML;
    }
}
