<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

// Update grid content elements palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'grid_preset';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-start']    = '{type_legend},type;{grid_legend},grid_preset;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['grid-stop']    = '{type_legend},type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_bs3'] = 'grid_cols';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_bs4'] = 'grid_cols';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['grid_preset_cssgrid'] = 'grid_rows,grid_cols';

// Update tl_content fields
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_preset'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('bs3', 'bs4', 'cssgrid'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_content']['grid_preset'],
	'eval'                    => array('tl_class'=>'', 'submitOnChange'=>true, 'includeBlankOption'=>true, 'chosen'=>true),
	'sql'                     => "varchar(32) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_rows'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_rows'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);
$GLOBALS['TL_DCA']['tl_content']['fields']['grid_cols'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['grid_cols'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

class tl_content_grid extends tl_content
{

}