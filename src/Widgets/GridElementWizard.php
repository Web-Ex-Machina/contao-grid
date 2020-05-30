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

namespace WEM\GridBundle\Widgets;

use WEM\GridBundle\Helper\GridBuilder;

class GridElementWizard extends \Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Default constructor.
     *
     * @param array $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
    }

    /**
     * Default Set.
     *
     * @param string $strKey
     */
    public function __set($strKey, $varValue): void
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
     * Validate the input and set the value.
     */
    public function validate(): void
    {
        $mandatory = $this->mandatory;
        $varValue = $this->getPost($this->strName);

        foreach ($varValue as $k => &$v) {
            // Skip _classes items
            if(false !== strpos((string) $k, "_classes")) {
                continue;
            }

            // Check if the _classes item for this key contains stuff
            // If true, concat the values
            if($varValue[$k.'_classes']) {
                $v .= ' '.$varValue[$k.'_classes'];
            }
        }

        parent::validate();
    }

    /**
     * Generate the widget and return it as string.
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
                throw new Exception('Unknown table for GridElementWizard : '.$this->strTable);
        }

        if (!$objItems || 0 === $objItems->count()) {
            throw new Exception('No items found for this grid');
        }

        $arrItems = [];
        $blnGridStart = false;
        $blnGridStop = false;
        $intGridStop = 0;

        $GLOBALS['WEM']['GRID'][$this->id] = [
            'preset' => $this->activeRecord->grid_preset,
            'wrapper_classes' => GridBuilder::getWrapperClasses($this->activeRecord),
            'item_classes' => GridBuilder::getItemClasses($this->activeRecord),
        ];

        if ('' !== $this->activeRecord->cssID[1]) {
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

        if ('' !== $strHelper) {
            $strHelper = '<div class="tl_info">'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['manualLabel'], $strHelper).'</div>';
        }

        // Now, we will only fetch the items in the grid
        while ($objItems->next()) {
            // If we start a grid, start fetching items for the wizard
            if ($objItems->id === $this->activeRecord->id) {
                $blnGridStart = true;
                ++$intGridStop;
                continue;
            }

            // Skip if we are not in a grid
            if (!$blnGridStart) {
                continue;
            }

            // If we hit another grid-start, increment the number of "grid stops" authorized
            if ('grid-start' === $objItems->type) {
                $GLOBALS['WEM']['GRID'][$objItems->id] = $GLOBALS['WEM']['GRID'][$this->id];
                $GLOBALS['WEM']['GRID'][$objItems->id]['subgrid'] = true;

                $strGridStartId = $objItems->id;
                ++$intGridStop;
            }

            // And break the loop if we hit a grid-stop element
            if ('grid-stop' === $objItems->type) {
                --$intGridStop;

                if (0 === $intGridStop) {
                    break;
                }
            }

            $objItems->isForGridElementWizard = true;
            $strElement = $this->getContentElement($objItems->current());

            // Add the input to the grid item
            $search = '</div>';
            $pos = strrpos($strElement, $search);

            // Build a select options html with the number of possibilities
            $options = '<option value="">-</option>';
            if (!\is_array($this->activeRecord->grid_cols)) {
                $cols = deserialize($this->activeRecord->grid_cols);
            } else {
                $cols = $this->activeRecord->grid_cols;
            }
            foreach ($cols as $c) {
                if ('all' === $c['key']) {
                    $v = $this->varValue[('grid-stop' === $objItems->type) ? $strGridStartId : $objItems->id];
                    for ($i = 1; $i <= $c['value']; ++$i) {
                        $options .= sprintf(
                            '<option value="cols-span-%s"%s>%s</option>',
                            $i,
                            ($v === 'cols-span-'.$i) ? ' selected' : '',
                            sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], $i)
                        );
                    }
                }
            }

            $select = sprintf(
                '<div class="item-classes">
                    <label for="ctrl_%1$s_%2$s">%4$s</label><select id="ctrl_%1$s_%2$s" name="%1$s[%2$s]" class="tl_select">%3$s</select>
                    <label for="ctrl_%1$s_%2$s_classes">%5$s</label><input type="text" id="ctrl_%1$s_%2$s_classes" name="%1$s[%2$s_classes]" class="tl_text" value="%6$s" />
                </div>',
                $this->strId,
                ('grid-stop' === $objItems->type) ? $strGridStartId : $objItems->id,
                $options,
                $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsSelectLabel'],
                $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['additionalClassesLabel'],
                $this->varValue[('grid-stop' === $objItems->type) ? $strGridStartId. '_classes' : $objItems->id . '_classes'],
            );

            if (false !== $pos && !\Input::get('grid_preview')) {
                $strElement = substr_replace($strElement, $select.$search, $pos, \strlen($search));
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
    <div class="helpers d-grid cols-3">
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
            <button class="tl_submit grid_toggleHelpers">'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['toggleHelpers'].'</button>
        </div>
    </div>
    '.$strHelper.'
    '.$strGrid.'
</div>';

        return $strReturn;
    }
}
