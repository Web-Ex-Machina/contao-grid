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
    protected function compile()
    {
        // Backend template
        if (TL_MODE == 'BE') {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new \BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type];
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        reset($GLOBALS['WEM']['GRID']);

        // If no elements contained in the grid, we don't need to close the wrapper (because it won't be opened)
        if (0 == count($arrGrid['elements'])) {
            $this->Template->doNotPrint = true;
        }

        // Send the grid_id to template
        $this->Template->grid_id = $arrGrid['grid_id'];
    }
}
