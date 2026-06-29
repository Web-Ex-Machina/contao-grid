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

// Generate Global Wrapper
$GLOBALS['WEM']['GRID'] = [];

// Add wrappers
$GLOBALS['TL_WRAPPERS']['start'][] = GridStart::ELEMENT_TYPE;
$GLOBALS['TL_WRAPPERS']['stop'][] = GridStop::ELEMENT_TYPE;

// Add Backend Wizard
$GLOBALS['BE_FFL']['gridElementWizard'] = Widgets\GridElementWizard::class;
$GLOBALS['BE_FFL']['gridBreakpointsValuesWizard'] = Widgets\GridBreakpointsValuesWizard::class;
$GLOBALS['BE_FFL']['gridGapValuesWizard'] = Widgets\GridGapValuesWizard::class;
