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
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate(){
		dump($this);
		dump($this->objDca);
		dump($this->objDca->activeRecord->ptable);
		dump($this->objDca->activeRecord->id);
	}
}