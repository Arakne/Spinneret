<?php

namespace Arakne\Tests\Spinneret\Util;

use Arakne\Spinneret\Util\Files;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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

        $this->rrmdir($dir);
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

        $this->rrmdir($dir);
    }

    // @todo Add utility method to Files
    public function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach (new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
