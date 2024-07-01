<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        LevelSetList::UP_TO_PHP_80,
        LevelSetList::UP_TO_PHP_81,
        LevelSetList::UP_TO_PHP_82,
        SetList::DEAD_CODE
    ]);
