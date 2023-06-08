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

use Contao\ContentModel;
use Contao\Input;
use Contao\Widget;
use WEM\GridBundle\Classes\GridOpenedManager;
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

    /** @var GridOpenedManager */
    protected $gridOpenedManager;

    /** @var GridBuilder */
    protected $gridBuilder;

    /**
     * Default constructor.
     *
     * @param array $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
        $this->gridOpenedManager = GridOpenedManager::getInstance();
        $this->gridBuilder = new GridBuilder();
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
        $this->import(\Contao\BackendUser::class, 'User');
        // Since it's only tl_content for the moment, it's a bit overkill, but it's to ease the future integrations.
        switch ($this->strTable) {
            case 'tl_content':
                $objItems = ContentModel::findPublishedByPidAndTable($this->objDca->activeRecord->pid, $this->objDca->activeRecord->ptable);
                break;

            default:
                throw new \Exception('Unknown table for GridElementWizard : '.$this->strTable);
        }

        if (!$objItems || 0 === $objItems->count()) {
            return '';
        }

        $blnGridStart = false;

        $this->gridOpenedManager->openGrid($this->activeRecord);
        $openedGrid = $this->gridOpenedManager->getLastOpenedGrid();

        $strGrid = sprintf('<div class="grid_preview %s" data-id="%s" data-grid-mode="%s">', implode(' ', $openedGrid->getWrapperClassesWithoutResolutionSpecificClasses()), $this->activeRecord->id, $this->activeRecord->grid_mode);

        $strGrid .= $this->gridBuilder->fakeFirstGridElementMarkup((string) $this->activeRecord->id);

        // Now, we will only fetch the items in the grid
        while ($objItems->next()) {
            // If we start a grid, start fetching items for the wizard
            if ($objItems->id === $this->activeRecord->id) {
                $blnGridStart = true;
                continue;
            }

            // Skip if we are not in a grid
            if (!$blnGridStart) {
                continue;
            }

            // And break the loop if we hit the grid-stop element corresponding to the very first grid
            if ('grid-stop' === $objItems->type) {
                if ($this->activeRecord->id === $this->gridOpenedManager->getLastOpenedGridId()) {
                    break;
                }
            }

            $objItems->isForGridElementWizard = true;
            if ('grid-start' === $objItems->type) {
                $strElement = $this->getContentElement($objItems->current());
            } elseif ('grid-stop' === $objItems->type) {
                $strElement = $this->BEGridItemSettings(
                    $this->gridOpenedManager->getPreviousLastOpenedGridId(),
                    $this->gridOpenedManager->getLastOpenedGridId(),
                    $this->getContentElement($objItems->current())
                );
            } else {
                $strElement = $this->BEGridItemSettings(
                    $this->gridOpenedManager->getLastOpenedGridId(),
                    $objItems->id,
                    $this->getContentElement($objItems->current())
                );
            }

            $strGrid .= $strElement;
        }

        // Add CSS & JS to the Wizard
        $this->addAssets();

        $strGrid .= $this->gridBuilder->fakeNewGridElementMarkup((string) $this->activeRecord->id);
        $strGrid .= $this->gridBuilder->fakeLastGridElementMarkup((string) $this->activeRecord->id);
        $strGrid .= '</div>';

        return '<div class="gridelement">
            '.$strGrid.'
        </div>';
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
        $grid = $this->gridOpenedManager->getGridById($gridId);

        if (false !== $pos) {
            $breakpoints = ['all', 'xl', 'lg', 'md', 'sm', 'xs', 'xxs'];
            $selectsCols = [];
            $selectsRows = [];
            $cols = $grid->getCols();
            foreach ($breakpoints as $breakpoint) {
                // Build a select options html with the number of possibilities
                $options = '<option value="">-</option>';
                foreach ($cols as $c) {
                    if ($breakpoint === $c['key']) {
                        $v = $grid->getItemClassesFormColsForItemIdAndResolution($objItemId, $breakpoint);
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

                $selectsCols[] = sprintf('
                        <label for="ctrl_%1$s_%2$s_cols_%5$s" class="%8$s" data-force-hidden="%7$s">%4$s</label>
                        <select id="ctrl_%1$s_%2$s_cols_%5$s" name="%1$s[%2$s_cols][%5$s]" class="tl_select %8$s" data-breakpoint="%5$s" data-item-id="%2$s" data-type="cols"  data-force-hidden="%7$s" data-previous-value="%6$s" %7$s>%3$s</select>',
                    $this->strId,
                    $objItemId,
                    $options,
                    $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsSelectLabel'],
                    $breakpoint,
                    $v,
                    '0', // we never force hidden here, JS will handle showing/hiding those elements, plus no restrictions about if user is admin or not (contrary to cols)
                    \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'hidden' : ''
                );

                $options = '<option value="">-</option>';

                for ($i = 1; $i <= 12; ++$i) {
                    $v = $grid->getItemClassesFormRowsForItemIdAndResolution($objItemId, $breakpoint);
                    $optionValue = sprintf('rows-span%s-%s', ('all' !== $breakpoint) ? '-'.$breakpoint : '', $i);
                    $options .= sprintf(
                        '<option value="%s"%s>%s</option>',
                        $optionValue,
                        ($v === $optionValue) ? ' selected' : '',
                        sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbRowsOptionLabel'], $i)
                    );
                }

                $selectsRows[] = sprintf('
                        <label for="ctrl_%1$s_%2$s_rows_%5$s" class="%8$s" data-force-hidden="%7$s">%4$s</label>
                        <select id="ctrl_%1$s_%2$s_rows_%5$s" name="%1$s[%2$s_rows][%5$s]" class="tl_select %8$s" data-breakpoint="%5$s" data-item-id="%2$s" data-type="rows" data-force-hidden="%7$s" data-previous-value="%6$s">%3$s</select>',
                    $this->strId,
                    $objItemId,
                    $options,
                    $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbRowsSelectLabel'],
                    $breakpoint,
                    $v,
                    !$this->User->isAdmin, // we only force hidden if user isn't admin
                    \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'hidden' : ($this->User->isAdmin ? '' : 'hidden')
                );
            }

            $inputClasses = sprintf(
                '<label for="ctrl_%1$s_%2$s_classes" class="%5$s">%3$s</label>
                <input type="text" id="ctrl_%1$s_%2$s_classes" name="%1$s[%2$s_classes]" class="tl_text %5$s" data-item-id="%2$s" value="%4$s" />',
                $this->strId,
                $objItemId,
                $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['additionalClassesLabel'],
                $grid->getItemClassesFormClassesForItemId($objItemId),
                $this->User->isAdmin ? '' : 'hidden'
            );

            // $itemSettings = sprintf(
            //     '<div class="item-classes">
            //         <div class="d-grid cols-1">
            //             <div>
            //             %s
            //             </div>
            //             <div>
            //             %s
            //             </div>
            //         </div>
            //         %s
            //     </div>',
            //     implode('', $selectsCols),
            //     implode('', $selectsRows),
            //     $inputClasses
            // );

            $itemSettings = sprintf(
                '<div class="item-classes">
                    %s
                    %s
                    %s
                </div>',
                implode('', $selectsCols),
                implode('', $selectsRows),
                $inputClasses
            );

            $strElement = substr_replace($strElement, $itemSettings.$search, $pos, \strlen($search));
        }

        return $strElement;
    }

    protected function addAssets(): void
    {
        $GLOBALS['TL_CSS']['wemgrid'] = 'bundles/wemgrid/css/backend.css';
        $GLOBALS['TL_JAVASCRIPT']['wemgrid'] = 'bundles/wemgrid/js/backend.js';
        $GLOBALS['TL_JAVASCRIPT']['wemgrid_translations'] = 'bundles/wemgrid/js/wem_grid_translations.js';
        $GLOBALS['TL_MOOTOOLS']['wemgrid'] = '<script>
            WEM.Grid.Translations.new = "'.$GLOBALS['TL_LANG']['DCA']['new'][1].'";
            WEM.Grid.Translations.inheritedColumns = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['inheritedColumns'].'";
            WEM.Grid.Translations.inheritedRows = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['inheritedRows'].'";
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
            WEM.Grid.Translations.breakpoints.all = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointAll'].'";
            WEM.Grid.Translations.breakpoints.xl = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXl'].'";
            WEM.Grid.Translations.breakpoints.lg = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointLg'].'";
            WEM.Grid.Translations.breakpoints.md = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointMd'].'";
            WEM.Grid.Translations.breakpoints.sm = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointSm'].'";
            WEM.Grid.Translations.breakpoints.xs = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXs'].'";
            WEM.Grid.Translations.breakpoints.xxs = "'.$GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXxs'].'";
        </script>';
    }
}
