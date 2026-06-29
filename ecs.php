<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;

use Contao\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' =>
"Contao Grid Bundle for Contao Open Source CMS
@author     Web Ex Machina

@see        https://github.com/Web-Ex-Machina/contao-grid
@license    https://www.gnu.org/licenses/lgpl+gpl-3.0.txt"])
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip([
        ArrayOpenerAndCloserNewlineFixer::class,
        __DIR__ . '/migrations',
        __DIR__ . '/vendor',
        __DIR__ . '/var',
        __DIR__ . '/config/jwt',
        __DIR__ . '/config/secrets',
        __DIR__ . '/config/bundles.php',
    ])
    ->withPreparedSets(
        symplify: true, 
        psr12: true, 
        common: true,
    );