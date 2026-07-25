<?php

use PhpCsFixer\Config;

return (new Config())
    ->setUsingCache(false)
    ->setRules([
        '@PER' => true,
        '@PHP83Migration' => true,
        'single_line_empty_body' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()->in(__DIR__.'/src')
    )
;
