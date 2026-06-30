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

use WEM\GridBundle\Widgets;

// Add Backend Wizard
$GLOBALS['BE_FFL']['gridElementWizard'] = Widgets\GridElementWizard::class;
$GLOBALS['BE_FFL']['gridBreakpointsValuesWizard'] = Widgets\GridBreakpointsValuesWizard::class;
$GLOBALS['BE_FFL']['gridGapValuesWizard'] = Widgets\GridGapValuesWizard::class;
