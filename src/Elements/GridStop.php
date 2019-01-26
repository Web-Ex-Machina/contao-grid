<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Elements;

/**
 * Content Element "grid-stop"
 */
class GridStop extends \ContentElement
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_grid_stop';

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

		$GLOBALS['WEM']['GRID'] = null;
	}
}