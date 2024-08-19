<?php

namespace Arakne\Spinneret\Util;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;

/**
 * Utility class for handle files and directories
 */
final class Files
{
    /**
     * Write the given content to the given filename
     * If the target directory does not exist, it will be created
     *
     * @param string $filename The file name to write. Should be a full path
     * @param string $content The content to write
     *
     * @return void
     */
    public static function write(string $filename, string $content): void
    {
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents($filename, $content);
    }

    /**
     * Write multiple files to the given target directory
     * If the target directory does not exist, it will be created
     *
     * @param string $targetDirectory The directory where to write the files
     * @param array<string, string> $files The files to write. The key is the filename and the value is the content.
     *
     * @return void
     */
    public static function writeAll(string $targetDirectory, array $files): void
    {
        foreach ($files as $filename => $content) {
            self::write($targetDirectory . '/' . $filename, $content);
        }
    }
}
