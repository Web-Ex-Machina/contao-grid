<?php 

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Helper;

/**
 * Function to centralize generic code to 
 */
class GridBuilder extends \Controller
{
	/**
	 * Generate wrapper classes, depending of the element
	 * @param  \ContentModel $objElement [description]
	 * @return [type]                    [description]
	 */
	public static function getWrapperClasses($objElement){
		$arrClasses = [];
		$rows = unserialize($objElement->grid_rows);
		$cols = unserialize($objElement->grid_cols);

		switch($objElement->grid_preset){
			case 'bs3':
				$arrClasses[] = $objElement->grid_row_class;
			break;

			case 'bs4':
				$arrClasses[] = $objElement->grid_row_class;

				// In BS4, we need row class in the wrapper
				if(!in_array('row', $arrClasses))
					$arrClasses[] = 'row';
			break;

			case 'cssgrid':
				$arrClasses[] = "d-grid";

				foreach($cols as $k => $col){
					// Quickfix : we need the first col to be generic, no matter what is the breakpoint
					if(0 == $k)
						$arrClasses[] = sprintf("cols-%d", $col['value']);
					else
						$arrClasses[] = sprintf("cols-%s-%d", $col['key'], $col['value']);
				}

				foreach($rows as $k=>$row){
					// Quickfix : we need the first col to be generic, no matter what is the breakpoint
					if(0 == $k)
						$arrClasses[] = sprintf("rows-%d", $row['value']);
					else
						$arrClasses[] = sprintf("rows-%s-%d", $row['key'], $row['value']);
				}
			break;

			default:
				throw new \Exception(sprintf("Preset %s unknown", $objElement->grid_preset));
		}

		return $arrClasses;
	}

	/**
	 * Generate item classes, depending of the element
	 * @param  \ContentModel $objElement [description]
	 * @return [type]                    [description]
	 */
	public static function getItemClasses($objElement){
		$arrClasses = [];
		$cols = unserialize($objElement->grid_rows);

		switch($objElement->grid_preset){
			case 'bs3':
				foreach($cols as $col)
					$arrClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
			break;

			case 'bs4':
				foreach($cols as $k => $col){
					// Quickfix : we need the first col to be generic, no matter what is the breakpoint
					if(0 == $k)
						$arrClasses[] = sprintf("col-%d", 12 / $col['value']);
					else
						$arrClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
				}
			break;

			case 'cssgrid':
				$arrClasses[] = 'item-grid';
			break;

			default:
				throw new \Exception(sprintf("Preset %s unknown", $objElement->grid_preset));
		}

		return $arrClasses;
	}
}