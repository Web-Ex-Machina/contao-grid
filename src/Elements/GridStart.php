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
        if (TL_MODE === 'BE' && !$this->isForGridElementWizard) {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new \BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type][1];
            $this->Template->wildcard = $GLOBALS['TL_LANG']['tl_content']['grid_preset'][$this->grid_preset];

            $this->arrGridBreakpoints = [
                ['name' => 'all', 'label' => 'Général', 'required'=>true],
                ['name' => 'xxs', 'start' => 0, 'stop' => 619, 'label' => 'XXS'],
                ['name' => 'xs', 'start' => 620, 'stop' => 767, 'label' => 'XS'],
                ['name' => 'sm', 'start' => 768, 'stop' => 991, 'label' => 'SM'],
                ['name' => 'md', 'start' => 992, 'stop' => 1199, 'label' => 'MD'],
                ['name' => 'lg', 'start' => 1200, 'stop' => 1399, 'label' => 'LG'],
                ['name' => 'xl', 'start' => 1400, 'stop' => 0, 'label' => 'XL'],
            ]; /** @todo - make it generic per grid */

            $breakpoints = [];
            $arrGridValues = GridBuilder::getWrapperClasses($this);
            foreach($arrGridValues as $k=>$b) {
                $b = explode("-", $b);
                if("cols" !== $b[0]) {
                    continue;
                } elseif (2 == count($b)) {
                    $breakpoint = $this->getBreakpointData("all");
                    $val = $b[1];
                } elseif(3 == count($b)) {
                    $breakpoint = $this->getBreakpointData($b[1]);
                    $val = $b[2];
                }

                if(0 != $val) {
                    $breakpoints[] = $breakpoint["label"].": ".sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], $val);
                }
            }
            $this->Template->wildcard .= ' | Config: '.implode(', ', $breakpoints);
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

        // We might have a preset already registered for that grid, so check it before erase the global key
        if (\is_array($GLOBALS['WEM']['GRID']) && !\array_key_exists($this->id, $GLOBALS['WEM']['GRID'])) {
            $GLOBALS['WEM']['GRID'][$this->id] = $arrGrid;
        }

        // Add the classes to the Model so the main class can use it correct
        if (\is_array($this->objModel->classes)) {
            $this->objModel->classes = array_merge($arrGrid['wrapper_classes'], $this->objModel->classes);
        } else {
            $this->objModel->classes = $arrGrid['wrapper_classes'];
        }

        // Send the grid_id to template
        $this->Template->grid_id = $this->id;
    }

    protected function getBreakpointData($name) {
        foreach($this->arrGridBreakpoints as $b) {
            if($name == $b['name']) {
                return $b;
            }
        }
    }
}
