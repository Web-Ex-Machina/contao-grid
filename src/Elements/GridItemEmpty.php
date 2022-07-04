<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\Elements;

use WEM\GridBundle\Classes\GridOpenedManager;

/**
 * Content Element "grid-stop".
 */
class GridItemEmpty extends \ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_grid_item_empty';

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
        }

        // Get the last open grid
        if (\is_array($GLOBALS['WEM']['GRID'])) {
            $gop = GridOpenedManager::getInstance();
            // Send the grid_id to template
            $this->Template->grid_id = $gop->getLastOpenedGridId();
        }
    }
}
