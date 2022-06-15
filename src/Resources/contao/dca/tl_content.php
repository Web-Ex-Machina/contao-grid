<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

// Update grid content elements callbacks
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = ['wem.helper.tl_content_callback', 'onsubmitCallback'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['wem.helper.tl_content_callback', 'includeJSCSS'];
$GLOBALS['TL_DCA']['tl_content']['config']['oncut_callback'][] = ['wem.helper.tl_content_callback', 'oncutCallback'];
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][] = ['wem.helper.tl_content_callback', 'oncopyCallback'];
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = ['wem.helper.tl_content_callback', 'ondeleteCallback'];

// Update grid content elements palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'grid_preset';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-start'] = '{type_legend},type;{grid_legend},grid_preset;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-stop'] = '{type_legend},type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_cssgrid'] = 'grid_cols,grid_gap,grid_items';

// Update tl_content fields
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_preset'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
    'default' => 'cssgrid',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['cssgrid'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
    'eval' => ['tl_class' => 'clr', 'submitOnChange' => true, 'chosen' => true],
    'sql' => "varchar(32) NOT NULL default 'cssgrid'",
];
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_row_class'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_row_class'],
    'default' => 'row',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['row', 'container'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['grid_row_class'],
    'eval' => ['chosen' => true],
    'sql' => "varchar(32) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_cols'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_cols'],
    'exclude' => true,
    'inputType' => 'gridBreakpointsValuesWizard',
    'eval' => ['tl_class' => ''],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_gap'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_gap'],
    'exclude' => true,
    'inputType' => 'gridGapValuesWizard',
    'eval' => ['tl_class' => ''],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_items'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['grid_items'],
    'exclude' => true,
    'inputType' => 'gridElementWizard',
    'eval' => ['tl_class' => 'clr'],
    'sql' => 'blob NULL',
];
