<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2020 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\Elements;

/**
 * Content Element "grid-stop".
 */
class GridStop extends \ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_grid_stop';

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        // Backend template
        if (TL_MODE === 'BE') {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new \BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type];
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        reset($GLOBALS['WEM']['GRID']);

        // If no elements contained in the grid, we don't need to close the wrapper (because it won't be opened)
        if (0 === \count($arrGrid['elements'])) {
            $this->Template->doNotPrint = true;
        }

        // Send the grid_id to template
        $this->Template->grid_id = $arrGrid['grid_id'];
    }
}
