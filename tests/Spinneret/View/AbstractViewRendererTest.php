<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\D;
use Arakne\Spinneret\View\View;
use Arakne\Tests\Spinneret\Fixtures\Hello\HelloResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractViewRendererTest extends TestCase
{
    #[Test]
    public function test()
    {
        $renderer = new class extends AbstractViewRenderer {
            public function __invoke(View $view, HelloResponse $data): void
            {
?>
<h1>Hello></h1>
<p>Hello, <?= htmlentities($data->name) ?>!</p>
<?php
            }
        };

        $response = new HelloResponse('John');

        $this->assertSame(<<<'HTML'
<h1>Hello></h1>
<p>Hello, John!</p>

HTML
, $renderer->render(new View($response), $response));

        ob_start();
        $renderer->display(new View($response), $response);
        $this->assertSame(<<<'HTML'
<h1>Hello></h1>
<p>Hello, John!</p>

HTML
, ob_get_clean());
    }
}
