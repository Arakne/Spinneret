<?php

use PhpCsFixer\Config;

return (new Config())
    ->setUsingCache(false)
    ->setRules([
        '@PSR12' => true,
        '@PHP83Migration' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()->in(__DIR__.'/src/Spinneret')
    )
;
