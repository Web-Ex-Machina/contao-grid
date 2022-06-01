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

namespace WEM\GridBundle\Widgets;

use Contao\Widget;
use WEM\GridBundle\Helper\GridBuilder;

class GridElementWizard extends Widget
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
            if (false !== strpos((string) $k, '_classes')) {
                continue;
            }

            // Check if the _classes item for this key contains stuff
            // If true, concat the values
            if ($varValue[$k.'_classes']) {
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
                throw new \Exception('Unknown table for GridElementWizard : '.$this->strTable);
        }

        if (!$objItems || 0 === $objItems->count()) {
            return '';
        }

        $arrItems = [];
        $blnGridStart = false;
        $blnGridStop = false;
        $intGridStop = 0;
        $currentGridId[] = $this->id;

        $GLOBALS['WEM']['GRID'][(string) $this->id] = [
            'preset' => $this->activeRecord->grid_preset,
            'cols' => !\is_array($this->activeRecord->grid_cols) ? deserialize($this->activeRecord->grid_cols) : $this->activeRecord->grid_cols,
            'wrapper_classes' => GridBuilder::getWrapperClasses($this->activeRecord),
            'item_classes' => GridBuilder::getItemClasses($this->activeRecord),
            'level' => 0,
            'id' => (string) $this->id,
        ];

        if ('' !== $this->activeRecord->cssID[1]) {
            $GLOBALS['WEM']['GRID'][$this->id]['wrapper_classes'][] = $this->cssID[1];
        }

        $GLOBALS['WEM']['GRID'][$this->id]['item_classes']['all'][] = 'be_item_grid helper';

        $strGrid = sprintf('<div class="grid_preview %s" data-id="%s">', implode(' ', $GLOBALS['WEM']['GRID'][$this->id]['wrapper_classes']), $this->activeRecord->id);

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
        if (!\Input::get('grid_preview')) {
            $strGrid .= GridBuilder::fakeFirstGridElementMarkup((string) $this->id);
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
                // $GLOBALS['WEM']['GRID'][$objItems->id] = $GLOBALS['WEM']['GRID'][$this->id];
                $GLOBALS['WEM']['GRID'][(string) $objItems->id] = [
                    'preset' => $objItems->grid_preset,
                    'cols' => !\is_array($objItems->grid_cols) ? deserialize($objItems->grid_cols) : $objItems->grid_cols,
                    'wrapper_classes' => GridBuilder::getWrapperClasses($objItems),
                    'item_classes' => GridBuilder::getItemClasses($objItems),
                    'id' => (string) $objItems->id,
                ];
                $GLOBALS['WEM']['GRID'][$objItems->id]['item_classes']['all'][] = 'be_item_grid helper';
                $GLOBALS['WEM']['GRID'][$objItems->id]['subgrid'] = true;
                $GLOBALS['WEM']['GRID'][$objItems->id]['level'] = $intGridStop;

                $strGridStartId = $objItems->id;
                ++$intGridStop;
            }

            // And break the loop if we hit a grid-stop element
            if ('grid-stop' === $objItems->type) {
                --$intGridStop;
                array_pop($currentGridId);
                if (0 === $intGridStop) {
                    break;
                }
            }

            $objItems->isForGridElementWizard = true;

            if ('grid-start' === $objItems->type) {
                $strElement = $this->getContentElement($objItems->current());
            } else {
                $strElement = $this->BEGridItemSettings(end($currentGridId), ('grid-stop' === $objItems->type) ? $strGridStartId : $objItems->id, $this->getContentElement($objItems->current()));
            }

            if ('grid-start' === $objItems->type) {
                $currentGridId[] = $objItems->id;
            }
            $strGrid .= $strElement;
        }

        // Add CSS & JS to the Wizard
        $this->addAssets();

        if (!\Input::get('grid_preview')) {
            $strGrid .= GridBuilder::fakeLastGridElementMarkup();
            $strGrid .= GridBuilder::fakeNewGridElementMarkup();
        }
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

    /**
     * Returns HTML markup to edit a grid item' settings.
     *
     * @param string $gridId     The grid id inside $GLOBALS['WEM']['GRID']
     * @param string $objItemId  The content element's id
     * @param string $strElement The generated HTML markup
     */
    protected function BEGridItemSettings(string $gridId, string $objItemId, string $strElement): string
    {
        // Add the input to the grid item
        $search = '</div>';
        $pos = strrpos($strElement, $search);

        if (false !== $pos && !\Input::get('grid_preview')) {
            $breakpoints = ['all', 'xxs', 'xs', 'sm', 'md', 'lg', 'xl'];
            $selects = [];
            foreach ($breakpoints as $breakpoint) {
                // Build a select options html with the number of possibilities
                $options = '<option value="">-</option>';
                $cols = $GLOBALS['WEM']['GRID'][$gridId]['cols'];
                foreach ($cols as $c) {
                    if ('all' === $c['key']) {
                        $v = $this->varValue[$objItemId][$breakpoint];
                        for ($i = 1; $i <= $c['value']; ++$i) {
                            $optionValue = sprintf('cols-span%s-%s', ('all' !== $breakpoint) ? '-'.$breakpoint : '', $i);
                            $options .= sprintf(
                                '<option value="%s"%s>%s</option>',
                                $optionValue,
                                ($v === $optionValue) ? ' selected' : '',
                                sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], $i)
                            );
                        }
                    }
                }

                $selects[] = sprintf('
                        <label for="ctrl_%1$s_%2$s_%6$s">%4$s</label><select id="ctrl_%1$s_%2$s_%6$s" name="%1$s[%2$s][%6$s]" class="tl_select" data-breakpoint="%6$s">%3$s</select>',
                    $this->strId,
                    $objItemId,
                    $options,
                    $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsSelectLabel'],
                    $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['additionalClassesLabel'],
                    $breakpoint
                );
            }

            $itemSettings = sprintf(
                '<div class="item-classes">
                    %3$s
                    <label for="ctrl_%1$s_%2$s_classes">%5$s</label><input type="text" id="ctrl_%1$s_%2$s_classes" name="%1$s[%2$s_classes]" class="tl_text" value="%6$s" />
                </div>',
                $this->strId,
                $objItemId,
                implode('', $selects),
                $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsSelectLabel'],
                $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['additionalClassesLabel'],
                $this->varValue[$objItemId.'_classes'],
            );

            $strElement = substr_replace($strElement, $itemSettings.$search, $pos, \strlen($search));
        }

        return $strElement;
    }

    protected function addAssets(): void
    {
        $GLOBALS['TL_CSS']['wemgrid'] = 'bundles/wemgrid/css/backend.css';
        $GLOBALS['TL_CSS']['wemgrid_bs'] = 'bundles/wemgrid/css/bootstrap-grid.min.css';
        $GLOBALS['TL_JAVASCRIPT']['wemgrid'] = 'bundles/wemgrid/js/backend.js';
        $GLOBALS['TL_JAVASCRIPT']['wemgrid_translations'] = 'bundles/wemgrid/js/wem_grid_translations.js';
        $GLOBALS['TL_MOOTOOLS']['wemgrid'] = '<script>
            WEM.Grid.Translations.new = "'.$GLOBALS['TL_LANG']['DCA']['new'][1].'";
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 1).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 2).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 3).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 4).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 5).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 6).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 7).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 8).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 9).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 10).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 11).'");
            WEM.Grid.Translations.columns.push("'.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], 12).'");
        </script>';
    }
}
