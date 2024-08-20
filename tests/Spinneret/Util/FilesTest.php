<?php

namespace Arakne\Tests\Spinneret\Util;

use Arakne\Spinneret\Util\Files;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FilesTest extends TestCase
{
    #[Test]
    public function write()
    {
        $dir = '/tmp/'.bin2hex(random_bytes(8));
        $filename = $dir.'/test.txt';

        Files::write($filename, 'Hello, world!');

        $this->assertFileExists($filename);
        $this->assertEquals('Hello, world!', file_get_contents($filename));

        Files::rmdir($dir);
    }

    #[Test]
    public function writeAll()
    {
        $dir = '/tmp/'.bin2hex(random_bytes(8));

        $files = [
            'test1.txt' => 'Hello, world!',
            'bar/test2.txt' => 'Hello, world again!',
        ];

        Files::writeAll($dir, $files);

        $this->assertFileExists($dir.'/test1.txt');
        $this->assertEquals('Hello, world!', file_get_contents($dir.'/test1.txt'));

        $this->assertFileExists($dir.'/bar/test2.txt');
        $this->assertEquals('Hello, world again!', file_get_contents($dir.'/bar/test2.txt'));

        Files::rmdir($dir);
    }

    #[Test]
    public function rmdir()
    {
        Files::rmdir('/tmp/not-found');

        $dir = '/tmp/'.bin2hex(random_bytes(8));

        $files = [
            'test1.txt' => 'Hello, world!',
            'bar/test2.txt' => 'Hello, world again!',
            'bar/test3.txt' => 'Hello, world again!',
            'bar/baz/test4.txt' => 'Hello, world again!',
        ];

        Files::writeAll($dir, $files);

        Files::rmdir($dir);
        $this->assertDirectoryDoesNotExist($dir);
    }

    #[Test]
    public function rmdirNotADirectory()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given path is not a directory');

        Files::rmdir('/dev/null');
    }
}
