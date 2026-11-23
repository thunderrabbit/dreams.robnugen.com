<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

// Same paths as phpcs.xml. Target is the PHP DreamHost serves dreams on.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/classes',
        __DIR__ . '/wwwroot',
        __DIR__ . '/prepend.php',
        __DIR__ . '/Tests',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/Tests/_output',
        __DIR__ . '/Tests/Support/_generated',
        // Vendored from Modernizing Legacy Applications in PHP.
        __DIR__ . '/classes/Mlaphp',
        // Per-site and gitignored.
        __DIR__ . '/classes/Config.php',
    ])
    ->withSets([LevelSetList::UP_TO_PHP_83]);
