<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Elements;

use Contao\CoreBundle\Exception\InternalServerErrorException;

/**
 * Content Element "grid-start"
 */
class GridStart extends \ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_grid_start';

	/**
	 * Generate the content element
	 */
	protected function compile(){
		// Backend template
		if (TL_MODE == 'BE'){
			$this->strTemplate = 'be_wildcard';
			$this->Template = new \BackendTemplate($this->strTemplate);
			$this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type];
		}

		$arrWrapperClasses = [];
		$arrElementClasses = [];

		$arrCols = unserialize($this->grid_cols);
		$arrWrapperClasses[] = $this->grid_row_class;

		switch($this->grid_preset){
			case 'bs3':
				foreach($arrCols as $col){
					$arrElementClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
				}
			break;

			case 'bs4':
				foreach($arrCols as $col){
					if('all' == $col['key'])
						$arrElementClasses[] = sprintf("col-%d", 12 / $col['value']);
					else
						$arrElementClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
				}

				// In BS4, we need row class in the wrapper
				if(!in_array('row', $arrWrapperClasses))
					$arrWrapperClasses[] = 'row';
			break;

			case 'cssgrid':

			break;

			default:
				throw new InternalServerErrorException(sprintf("Preset %s unknown", $this->grid_preset));
		}

		$GLOBALS['WEM']['GRID']['preset'] = $this->grid_preset;
		$GLOBALS['WEM']['GRID']['classes'] = implode(' ', $arrElementClasses);

		$this->Template->classes = $arrWrapperClasses;
	}
}