<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\Helper;

use Contao\ContentModel;
use Contao\Database\Result as DatabaseResult;
use Contao\StringUtil;
use Contao\System;
use WEM\GridBundle\Classes\GridOpenedManager;
use WEM\GridBundle\Elements\GridStart;

/**
 * Function to centralize generic code to.
 */
class GridBuilder
{
    /**
     * Returns an array of CSS classes for the wrapper element of a grid.
     *
     * @param ContentModel|DatabaseResult $objElement the content element object
     *
     * @throws \Exception
     *
     * @return array the array of CSS classes
     */
    public static function getWrapperClasses($objElement): array
    {
        $arrClasses = [];

        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');

        if (!\is_array($objElement->grid_rows)) {
            $rows = StringUtil::deserialize($objElement->grid_rows);
        } else {
            $rows = $objElement->grid_rows;
        }

        if (!\is_array($objElement->grid_cols)) {
            $cols = StringUtil::deserialize($objElement->grid_cols);
        } else {
            $cols = $objElement->grid_cols;
        }

        if (!\is_array($objElement->grid_gap)) {
            $gap = StringUtil::deserialize($objElement->grid_gap);
        } else {
            $gap = $objElement->grid_gap;
        }

        // We don't need rows, but what's the point of a grid without cols ?
        // if (!\is_array($cols)) {
        // return [];
        // }
        $arrClasses[] = 'd-grid';

        if (GridStart::MODE_AUTOMATIC === $objElement->grid_mode) {
            $arrClasses[] = 'cols-autofit';
        } elseif (GridStart::MODE_CUSTOM === $objElement->grid_mode) {
            foreach ($cols as $k => $col) {
                // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                if (0 === $k) {
                    $arrClasses[] = sprintf('cols-%d', $col['value']);
                } elseif ($scopeMatcher->isFrontend()) {
                    if (0 !== (int) $col['value']) {
                        $arrClasses[] = sprintf('cols-%s-%d', $col['key'], $col['value']);
                    }
                } else {
                    $arrClasses[] = sprintf('cols-%s-%d', $col['key'], $col['value']);
                }
            }

            if (\is_array($rows)) {
                foreach ($rows as $k => $row) {
                    // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                    if (0 === $k) {
                        $arrClasses[] = sprintf('rows-%d', $row['value']);
                    } elseif ($scopeMatcher->isFrontend()) {
                        if (0 !== (int) $row['value']) {
                            $arrClasses[] = sprintf('rows-%s-%d', $row['key'], $row['value']);
                        }
                    } else {
                        $arrClasses[] = sprintf('rows-%s-%d', $row['key'], $row['value']);
                    }
                }
            }
        }

        if (\is_array($gap)) {
            $arrClasses[] = sprintf('gap-%d%s', $gap['value'], '' !== $gap['unit'] ? sprintf('-%s', $gap['unit']) : '');
        }

        return $arrClasses;
    }

    /**
     * Generate item classes, depending on the element.
     *
     * @param ContentModel|DatabaseResult $objElement the grid element object
     * @param bool|null                   $forForm    if true, prepares the classes for rendering in a form, for rendering in the grid
     *
     * @return array an array of classes for the grid item element
     */
    public static function getItemClasses($objElement, ?bool $forForm = false): array
    {
        $arrClasses = [];
        if (\is_array($objElement->grid_cols)) {
            $cols = $objElement->grid_cols;
        } else {
            $cols = StringUtil::deserialize($objElement->grid_cols);
        }

        $arrClasses[] = 'item-grid';

        // Setup special item rules
        $arrItemsClasses = [];
        if (null !== $objElement->grid_items) {
            if (!\is_array($objElement->grid_items)) {
                $items = StringUtil::deserialize($objElement->grid_items);
            } else {
                $items = $objElement->grid_items;
            }

            if (!$forForm) {
                foreach ($items as $itemId => $classes) {
                    if (\is_array($classes)) {
                        $items[$itemId] = preg_replace('/([\s]+)/', ' ', trim(implode(' ', $classes)));
                    }
                }
            }

            if (0 < \count($items)) {
                $arrItemsClasses = $items;
            }
        }

        return ['all' => $arrClasses, 'items' => $arrItemsClasses];
    }

    /**
     * Returns a "fake" grid element to allow element to be placed at the beggining of the grid.
     *
     * @param string $gridId The grid's id
     *
     * @throws \Exception
     */
    public function fakeFirstGridElementMarkup(string $gridId): string
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        // $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-',$grid->getWrapperClasses()[1]);
        $additionnalCssClasses = GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-', implode(' ', $grid->getWrapperColsClassesWithoutResolutionSpecificClasses()));

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-first-element">%s</div>', $additionnalCssClasses, $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridStart']);
    }

    /**
     * Returns a "fake" grid element to allow element to be placed at the end of the grid.
     *
     * @throws \Exception
     */
    public function fakeLastGridElementMarkup(string $gridId): string
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        // $additionnalCssClasses = GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-',$grid->getWrapperClasses()[1]);
        $additionnalCssClasses = GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-', implode(' ', $grid->getWrapperColsClassesWithoutResolutionSpecificClasses()));

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-last-element">%s</div>', $additionnalCssClasses, $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridEnd']);
    }

    /**
     * Returns a "fake" grid element to allow new elements to be added at the end of the grid.
     *
     * @throws \Exception
     */
    public function fakeNewGridElementMarkup(string $gridId): string // TODO : One day, delete all the function because she is useless.
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        $additionnalCssClasses = GridStart::MODE_AUTOMATIC === $grid->getMode() ? '' : '';

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="false" draggable="false" data-type="fake-new-element"><div class="item-new"></div></div>', $additionnalCssClasses);
    }
}
