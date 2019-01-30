<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Widgets;

use WEM\GridBundle\Helper\GridBuilder;

class GridElementWizard extends \Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;
/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_widget';

	/**
	 * Default constructor
	 *
	 * @param array $arrAttributes
	 */
	public function __construct($arrAttributes=null){
		parent::__construct($arrAttributes);
	}

	/**
	 * Default Set
	 *
	 * @param string $strKey
	 * @param mixed  $varValue
	 */
	public function __set($strKey, $varValue){
		switch ($strKey){
			case 'mandatory':
				if($varValue)
					$this->arrAttributes['required'] = 'required';
				else
					unset($this->arrAttributes['required']);

				parent::__set($strKey, $varValue);
			break;

			default:
				parent::__set($strKey, $varValue);
		}
	}

	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		$mandatory = $this->mandatory;
		$items = $this->getPost($this->strName);

	}

	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate(){
		// Since it's only tl_content for the moment, it's a bit overkill, but it's to ease the future integrations.
		switch($this->strTable){
			case 'tl_content':
				$objItems = \ContentModel::findPublishedByPidAndTable($this->objDca->activeRecord->pid, $this->objDca->activeRecord->ptable);
			break;

			default:
				throw new Exception("Unknown table for GridElementWizard : ".$this->strTable);
		}
	
		if(!$objItems || 0 === $objItems->count())
			throw new Exception("No items found for this grid");

		$arrItems = [];
		$blnGridStart = false;
		$blnGridStop = false;

		$strReturn = sprintf('<div class="%s">', implode(' ', GridBuilder::getWrapperClasses($this->activeRecord)));

		// Now, we will only fetch the items in the grid
		while($objItems->next()){
			// If we start a grid, start fetching items for the wizard
			if($objItems->id == $this->activeRecord->id){
				$blnGridStart = true;
				continue;
			}

			// Skip if we are not in a grid
			if(!$blnGridStart)
				continue;
			
			// And break the loop if we hit a grid-stop element
			if("grid-stop" == $objItems->type)
				break;

			$strReturn .= sprintf('<div class="%s">%s</div>', implode(' ', GridBuilder::getItemClasses($this->activeRecord)), $this->getContentElement($objItems->current()));
		}
		
		// Add CSS & JS to the Wizard
		$GLOBALS['TL_CSS']['wemgrid'] = 'bundles/wemgrid/css/backend.css';

		$strReturn .= '</div>';
		return $strReturn;

		
	}
}