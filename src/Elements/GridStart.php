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

use WEM\GridBundle\Helper\GridBuilder;

/**
 * Content Element "grid-start".
 */
class GridStart extends \ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_grid_start';

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        // Backend template
        if (TL_MODE === 'BE') {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new \BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type][1];
            $this->Template->wildcard = $GLOBALS['TL_LANG']['tl_content']['grid_preset'][$this->grid_preset];
            $this->Template->wildcard .= ' | Wrapper: '.implode(' ', GridBuilder::getWrapperClasses($this));
        }

        // Check if the very next element is a grid-stop element
        $objNextElement = \Database::getInstance()->prepare('SELECT * FROM tl_content WHERE pid = ? AND ptable = ? AND sorting > ? AND invisible = "" ORDER BY sorting ASC')->limit(1)->execute([$this->pid, $this->ptable, $this->sorting]);

        if (1 > $objNextElement->numRows || 'grid-stop' === $objNextElement->type) {
            $this->Template->doNotPrint = true;
        }

        // Set up wrappers classes
        $arrGrid = [
            'grid_id' => $this->id,
            'subgrid' => (\is_array($GLOBALS['WEM']['GRID']) && 1 <= \count($GLOBALS['WEM']['GRID'])) ? true : false,
            'preset' => $this->grid_preset,
            'wrapper_classes' => GridBuilder::getWrapperClasses($this),
            'item_classes' => GridBuilder::getItemClasses($this),
            'elements' => [],
        ];
        $GLOBALS['WEM']['GRID'][$this->id] = $arrGrid;

        // Add the classes to the Model so the main class can use it correct
        if (\is_array($this->objModel->classes)) {
            $this->objModel->classes = array_merge($arrGrid['wrapper_classes'], $this->objModel->classes);
        } else {
            $this->objModel->classes = $arrGrid['wrapper_classes'];
        }

        // Send the grid_id to template
        $this->Template->grid_id = $this->id;
    }
}
