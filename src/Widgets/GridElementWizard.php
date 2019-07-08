<?php

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Widgets;

use WEM\GridBundle\Helper\GridBuilder;

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
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
    }

    /**
     * Default Set
     *
     * @param string $strKey
     * @param mixed  $varValue
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'mandatory':
                if ($varValue) {
                    $this->arrAttributes['required'] = 'required';
                } else {
                    unset($this->arrAttributes['required']);
                }

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
        $varValue = $this->getPost($this->strName);
        parent::validate();
    }

    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        // Since it's only tl_content for the moment, it's a bit overkill, but it's to ease the future integrations.
        switch ($this->strTable) {
            case 'tl_content':
                $objItems = \ContentModel::findPublishedByPidAndTable($this->objDca->activeRecord->pid, $this->objDca->activeRecord->ptable);
                break;

            default:
                throw new Exception("Unknown table for GridElementWizard : ".$this->strTable);
        }
    
        if (!$objItems || 0 === $objItems->count()) {
            throw new Exception("No items found for this grid");
        }

        $arrItems = [];
        $blnGridStart = false;
        $blnGridStop = false;

        $GLOBALS['WEM']['GRID'][$this->id] = [
            "preset" => $this->activeRecord->grid_preset,
            "wrapper_classes" => GridBuilder::getWrapperClasses($this->activeRecord),
            "item_classes" => GridBuilder::getItemClasses($this->activeRecord)
        ];
        
        if ("" !== $this->activeRecord->cssID[1]) {
            $GLOBALS['WEM']['GRID'][$this->id]['wrapper_classes'][] = $this->cssID[1];
        }

        $GLOBALS['WEM']['GRID'][$this->id]['item_classes']['all'][] = 'be_item_grid helper';

        $strGrid = sprintf('<div class="grid_preview %s">', implode(' ', $GLOBALS['WEM']['GRID'][$this->id]['wrapper_classes']));

        switch ($this->activeRecord->grid_preset) {
            case 'cssgrid':
                $strHelper = sprintf(
                    '<a href="%s" title="%s" target="_blank">%s</a>',
                    'https://framway.webexmachina.fr/#framway__manuals-grid',
                    'Framway Grid Manual',
                    'Framway Grid Manual'
                );
                break;
            
            case 'bs4':
                $strHelper = sprintf(
                    '<a href="%s" title="%s" target="_blank">%s</a>',
                    'https://getbootstrap.com/docs/4.0/layout/grid/',
                    'BS4 Grid Manual',
                    'BS4 Grid Manual'
                );
                break;

            default:
                $strHelper = '';
                break;
        }

        if ('' != $strHelper) {
            $strHelper = '<div class="tl_info">'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['manualLabel'], $strHelper).'</div>';
        }

        // Now, we will only fetch the items in the grid
        while ($objItems->next()) {
            // If we start a grid, start fetching items for the wizard
            if ($objItems->id == $this->activeRecord->id) {
                $blnGridStart = true;
                continue;
            }

            // Skip if we are not in a grid
            if (!$blnGridStart) {
                continue;
            }
            
            // And break the loop if we hit a grid-stop element
            if ("grid-stop" == $objItems->type) {
                break;
            }

            $strElement = $this->getContentElement($objItems->current());

            // Tricky but works all the time : replace the last 6 characters (</div>) with the input
            $search = '</div>';
            $pos = strrpos($strElement, $search);
            if ($pos !== false && !\Input::get('grid_preview')) {
                $replace = sprintf(
                    '<div class="item-classes"><input name="%s[%s]" type="text" value="%s" placeholder="%s" /></div>',
                    $this->strId,
                    $objItems->id,
                    $this->varValue[$objItems->id],
                    $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['inputItemPlaceholder']
                ) . $search;
                $strElement = substr_replace($strElement, $replace, $pos, strlen($search));
            }

            $strGrid .= $strElement;
        }
        
        // Add CSS & JS to the Wizard
        $GLOBALS['TL_CSS']['wemgrid'] = 'bundles/wemgrid/css/backend.css';
        $GLOBALS['TL_CSS']['wemgrid_bs'] = 'bundles/wemgrid/css/bootstrap-grid.min.css';
        $GLOBALS['TL_JAVASCRIPT']['wemgrid'] = 'bundles/wemgrid/js/backend.js';

        $strGrid .= '</div>';

        // If we want a preview modal, catch & break
        if (\Input::get('grid_preview')) {
            $objTemplate = new \BackendTemplate('be_grid_preview');
            $objTemplate->grid = $strGrid;
            $objTemplate->css = $GLOBALS['TL_CSS'];
            $objResponse = new \Haste\Http\Response\HtmlResponse($objTemplate->parse());
            $objResponse->send();
        }

        $strReturn =
        '<div class="gridelement">
    <div class="helpers d-grid cols-4">
        <div class="item-grid">
            <span class="label">'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['previewLabel'].' :</span>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="xxs">XXS</button>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="xs">XS</button>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="sm">SM</button>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="md">MD</button>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="lg">LG</button>
            <button class="tl_submit grid_toggleBreakPoint" data-breakpoint="xl">XL</button>
        </div>
        <div class="item-grid">
            <button class="tl_submit grid_toggleHelpers">Toggle helpers</button>
        </div>
    </div>
    '.$strHelper.'
    '.$strGrid.'
</div>';

        return $strReturn;
    }
}
