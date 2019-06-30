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

        $GLOBALS['WEM']['GRID'] = [
            "preset" => $this->grid_preset,
            "wrapper_classes" => GridBuilder::getWrapperClasses($this),
            "item_classes" => GridBuilder::getItemClasses($this)
        ];
        
        if (is_array($this->objModel->classes)) {
            $this->objModel->classes = array_merge($GLOBALS['WEM']['GRID']['wrapper_classes'], $this->objModel->classes);
        } else {
            $this->objModel->classes = $GLOBALS['WEM']['GRID']['wrapper_classes'];
        }
    }
}
