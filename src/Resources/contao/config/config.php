<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

// Add the Grid Wrapper Content Element
array_insert(
    $GLOBALS['TL_CTE'],
    \count($GLOBALS['TL_CTE']) + 1,
    [
        'grid' => [
            'grid-start' => 'WEM\GridBundle\Elements\GridStart', 'grid-stop' => 'WEM\GridBundle\Elements\GridStop',
        ],
    ]
);

// Add Hook
$GLOBALS['TL_HOOKS']['getContentElement'][] = ['WEM\GridBundle\Classes\Hooks', 'wrapGridElements'];

// Add Backend Wizard
$GLOBALS['BE_FFL']['gridElementWizard'] = 'WEM\GridBundle\Widgets\GridElementWizard';
$GLOBALS['BE_FFL']['gridBreakpointsValuesWizard'] = 'WEM\GridBundle\Widgets\GridBreakpointsValuesWizard';
