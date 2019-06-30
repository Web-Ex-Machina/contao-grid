<?php

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

// Update grid content elements callbacks
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('\WEM\GridBundle\Helper\GridBuilder', 'createGridStop');

// Update grid content elements palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][]  = 'grid_preset';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-start']      = '{type_legend},type;{grid_legend},grid_preset;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-stop']       = '{type_legend},type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_bs4']      = 'grid_row_class,grid_cols,grid_items';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_cssgrid']  = 'grid_rows,grid_cols,grid_items';

// Update tl_content fields
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_preset'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
    'default'                 => 'cssgrid',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('bs4', 'cssgrid'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
    'eval'                    => array('tl_class'=>'', 'submitOnChange'=>true, 'includeBlankOption'=>true, 'chosen'=>true),
    'sql'                     => "varchar(32) NOT NULL default 'cssgrid'"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_row_class'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_row_class'],
    'default'                 => 'row',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('row', 'container'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_content']['grid_row_class'],
    'eval'                    => array('chosen'=>true),
    'sql'                     => "varchar(32) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_rows'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_rows'],
    'exclude'                 => true,
    'inputType'               => 'keyValueWizard',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_cols'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_cols'],
    'exclude'                 => true,
    'inputType'               => 'keyValueWizard',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_items'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_items'],
    'exclude'                 => true,
    'inputType'               => 'gridElementWizard',
    'eval'                    => array('tl_class'=>'clr'),
    'sql'                     => "blob NULL"
);

class tl_content_wem_grid extends tl_content
{
    
}
