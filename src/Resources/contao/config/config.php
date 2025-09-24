<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

use Contao\ArrayUtil;
use WEM\GridBundle\Elements\GridItemEmpty;
use WEM\GridBundle\Elements\GridStart;
use WEM\GridBundle\Elements\GridStop;
use WEM\GridBundle\Widgets;

// Add the Grid Wrapper Content Element
ArrayUtil::arrayInsert(
    $GLOBALS['TL_CTE'],
    \count($GLOBALS['TL_CTE']) + 1,
    [
        'grid' => [
            GridStart::ELEMENT_TYPE => GridStart::class,
            GridStop::ELEMENT_TYPE => GridStop::class,
            GridItemEmpty::ELEMENT_TYPE => GridItemEmpty::class,
        ],
    ]
);

// Generate Global Wrapper
$GLOBALS['WEM']['GRID'] = [];

// Add wrappers
$GLOBALS['TL_WRAPPERS']['start'][] = GridStart::ELEMENT_TYPE;
$GLOBALS['TL_WRAPPERS']['stop'][] = GridStop::ELEMENT_TYPE;

// Add Hook
$GLOBALS['TL_HOOKS']['getContentElement'][] = ['wem.grid.event_listener.get_content_element', '__invoke'];

// Add Backend Wizard
$GLOBALS['BE_FFL']['gridElementWizard'] = Widgets\GridElementWizard::class;
$GLOBALS['BE_FFL']['gridBreakpointsValuesWizard'] = Widgets\GridBreakpointsValuesWizard::class;
$GLOBALS['BE_FFL']['gridGapValuesWizard'] = Widgets\GridGapValuesWizard::class;
