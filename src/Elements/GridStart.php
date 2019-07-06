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

use WEM\GridBundle\Helper\GridBuilder;

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
    protected function compile()
    {
        // Backend template
        if (TL_MODE == 'BE') {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new \BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type];
        }

        // Check if the very next element is a grid-stop element
        $objNextElement = \Database::getInstance()->prepare('SELECT * FROM tl_content WHERE pid = ? AND ptable = ? AND sorting > ? AND invisible = "" ORDER BY sorting ASC')->limit(1)->execute([$this->pid, $this->ptable, $this->sorting]);

        if (1 > $objNextElement->numRows || "grid-stop" == $objNextElement->type) {
            $this->Template->doNotPrint = true;
        }

        // Set up wrappers classes
        $arrGrid = [
            "grid_id" => $this->id,
            "preset" => $this->grid_preset,
            "wrapper_classes" => GridBuilder::getWrapperClasses($this),
            "item_classes" => GridBuilder::getItemClasses($this),
            "elements" => []
        ];
        $GLOBALS['WEM']['GRID'][$this->id] = $arrGrid;
        
        // Add the classes to the Model so the main class can use it correct
        if (is_array($this->objModel->classes)) {
            $this->objModel->classes = array_merge($arrGrid['wrapper_classes'], $this->objModel->classes);
        } else {
            $this->objModel->classes = $arrGrid['wrapper_classes'];
        }

        // Send the grid_id to template
        $this->Template->grid_id = $this->id;
    }
}
