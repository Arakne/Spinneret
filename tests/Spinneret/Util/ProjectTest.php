<?php

namespace Arakne\Tests\Spinneret\Util;

use Arakne\Spinneret\Util\Project;
use Arakne\Tests\Spinneret\Util\Fixtures\A\B;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProjectTest extends TestCase
{
    #[Test]
    public function directory()
    {
        $this->assertEquals(__DIR__.'/Fixtures', Project::directory(B::class));
        $this->assertEquals(dirname(__FILE__, 4), Project::directory(self::class));

        $file = tempnam('/tmp', 'test');
        $className = 'Foo'.bin2hex(random_bytes(8));
        file_get_contents($file, '<?php class '.$className.' {}');

        include $file;
    }

    #[Test]
    public function directoryMissingComposer()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not locate the project root directory.');

        $file = tempnam('/tmp', 'test');
        $className = 'Foo'.bin2hex(random_bytes(8));
        file_put_contents($file, '<?php class '.$className.' {}');

        include $file;

        Project::directory($className);
        unlink($file);
    }

    #[Test]
    public function directoryBadClassName()
    {
        $this->expectException(\ReflectionException::class);

        Project::directory('invalid');
    }
}
