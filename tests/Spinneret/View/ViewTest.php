<?php

namespace Arakne\Tests\Spinneret\View;

use Arakne\Spinneret\View\View;
use Arakne\Spinneret\View\ViewEngineInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class ViewTest extends TestCase
{
    #[Test]
    public function extends()
    {
        $view = new View(
            $this->createMock(ViewEngineInterface::class),
            new stdClass(),
        );

        $this->assertNull($view->parent());

        $view->extends($parent = new stdClass());
        $this->assertSame($parent, $view->parent());

        try {
            $view->extends(new stdClass());
            $this->fail('An exception should have been thrown');
        } catch (\LogicException $e) {
            $this->assertSame('Parent view is already set', $e->getMessage());
        }
    }
}
