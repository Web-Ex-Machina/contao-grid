<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Widgets;

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

		$cols = unserialize($this->activeRecord->grid_cols);
		$rows = unserialize($this->activeRecord->grid_rows);

		if("cssgrid" == $this->activeRecord->grid_preset){
			$arrWrapperClasses[] = "d-grid";
			$arrElementClasses[] = 'item-grid';

			if(1 == count($cols)){
				$arrWrapperClasses[] = sprintf("cols-%d", 12 / $cols[0]['value']);
			}
			else{
				foreach($cols as $col){
					if('all' == $col['key'])
						$arrWrapperClasses[] = sprintf("cols-%d", 12 / $col['value']);
					else
						$arrWrapperClasses[] = sprintf("cols-%s-%d", $col['key'], 12 / $col['value']);
				}
			}

			if(1 == count($rows)){
				$arrWrapperClasses[] = sprintf("rows-%d", $rows[0]['value']);
			}
			else{
				foreach($rows as $row){
					if('all' == $row['key'])
						$arrWrapperClasses[] = sprintf("rows-%d", $row['value']);
					else
						$arrWrapperClasses[] = sprintf("rows-%s-%d", $row['key'], $row['value']);
				}
			}
		}
		else{
			$arrWrapperClasses[] = $this->activeRecord->grid_row_class;
			if(1 == count($cols)){
				$arrElementClasses[] = sprintf("col-%d", 12 / $cols[0]['value']);
			}
			else{
				foreach($cols as $col){
					if('all' == $col['key'])
						$arrElementClasses[] = sprintf("col-%d", 12 / $col['value']);
					else
						$arrElementClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
				}
			}

			// In BS4, we need row class in the wrapper
			if(!in_array('row', $arrWrapperClasses))
				$arrWrapperClasses[] = 'row';
		}

		$strReturn = sprintf('<div class="%s">', implode(' ', $arrWrapperClasses));

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

			$strReturn .= sprintf('<div class="%s">%s</div>', implode(' ', $arrElementClasses), $this->getContentElement($objItems->current()));

			// Store the item in any other cases
			$arrItems[] = $objItems->row();
		}

		$strReturn .= '</div>';

		dump($arrItems);

		return $strReturn;

		/*dump($this);
		dump($this->objDca);
		dump($this->strTable)
		dump($this->objDca->activeRecord->ptable);
		dump($this->objDca->activeRecord->id);*/
	}
}