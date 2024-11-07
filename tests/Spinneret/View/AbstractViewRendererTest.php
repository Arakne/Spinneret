<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\AbstractViewRenderer;
use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewEngineInterface;
use Arakne\Tests\Spinneret\Application\Fixtures\Hello\HelloResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function ob_get_level;

class AbstractViewRendererTest extends TestCase
{
    #[Test]
    public function test()
    {
        $engine = $this->createMock(ViewEngineInterface::class);
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
, $renderer->render(new View($engine, $response), $response));

        ob_start();
        $renderer->display(new View($engine, $response), $response);
        $this->assertSame(<<<'HTML'
<h1>Hello></h1>
<p>Hello, John!</p>

HTML
, ob_get_clean());
    }

    #[Test]
    public function renderWithExceptionShouldCleanOutputBuffer()
    {
        $engine = $this->createMock(ViewEngineInterface::class);
        $renderer = new class extends AbstractViewRenderer {
            public function __invoke(View $view, HelloResponse $data): void
            {
?>
<h1>Hello></h1>
<?php
                throw new \RuntimeException('An error occurred');
            }
        };

        $response = new HelloResponse('John');
        $level = ob_get_level();

        try {
            $renderer->render(new View($engine, $response), $response);
            $this->fail('An exception should have been thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('An error occurred', $e->getMessage());
        }

        $this->assertSame($level, ob_get_level());
    }
}
