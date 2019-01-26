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

		switch($this->grid_preset){
			case 'bs3':
				$arrWrapperClasses[] = "row"; // TODO > handle row or container, by asking in backend
			break;

			case 'bs4':
				$arrWrapperClasses[] = "row"; // TODO > handle row or container, by asking in backend
				$arrElementClasses[] = sprintf("col-%d", 12 / $this->grid_cols);
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