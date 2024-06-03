<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

use Contao\ArrayUtil;
use WEM\GridBundle\Elements;
use WEM\GridBundle\Widgets;

// Add the Grid Wrapper Content Element
ArrayUtil::arrayInsert(
    $GLOBALS['TL_CTE'],
    \count($GLOBALS['TL_CTE']) + 1,
    [
        'grid' => [
            'grid-start' => Elements\GridStart::class,
            'grid-stop' => Elements\GridStop::class,
            'grid-item-empty' => Elements\GridItemEmpty::class,
        ],
    ]
);

// Generate Global Wrapper
$GLOBALS['WEM']['GRID'] = [];

// Add wrappers
$GLOBALS['TL_WRAPPERS']['start'][] = 'grid-start';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'grid-stop';

// Add Hook
$GLOBALS['TL_HOOKS']['getContentElement'][] = ['wem.grid.event_listener.get_content_element', '__invoke'];

// Add Backend Wizard
$GLOBALS['BE_FFL']['gridElementWizard'] = Widgets\GridElementWizard::class;
$GLOBALS['BE_FFL']['gridBreakpointsValuesWizard'] = Widgets\GridBreakpointsValuesWizard::class;
$GLOBALS['BE_FFL']['gridGapValuesWizard'] = Widgets\GridGapValuesWizard::class;
