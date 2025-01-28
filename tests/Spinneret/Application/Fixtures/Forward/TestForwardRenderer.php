<?php

namespace Arakne\Tests\Spinneret\Application\Fixtures\Forward;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\ForwarderInterface;
use Arakne\Spinneret\View\View;
use Arakne\Tests\Spinneret\Application\Fixtures\Configurable\ShowConfigRequest;

class TestForwardRenderer extends AbstractViewRenderer
{
    public function __construct(
        private readonly ForwarderInterface $forwarder,
    ) {}

    public function __invoke(View $view, TestForwardResponse $data): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>Test Forward</title>
            </head>
            <body>
                <h1>Test Forward</h1>
                <pre><?php $this->forwarder->display(new ShowConfigRequest(), $view) ?></pre>
            </body>
        </html>
        <?php
    }
}
