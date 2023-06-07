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

namespace WEM\GridBundle\Helper;

use Contao\ContentModel;
use Contao\Database\Result as DatabaseResult;
use WEM\GridBundle\Classes\GridOpenedManager;

/**
 * Function to centralize generic code to.
 */
class GridBuilder
{
    /**
     * Generate wrapper classes, depending of the element.
     *
     * @param \ContentModel $objElement [description]
     *
     * @return [type] [description]
     */
    public static function getWrapperClasses($objElement)
    {
        $arrClasses = [];

        if (!\is_array($objElement->grid_rows)) {
            $rows = \Contao\StringUtil::deserialize($objElement->grid_rows);
        } else {
            $rows = $objElement->grid_rows;
        }

        if (!\is_array($objElement->grid_cols)) {
            $cols = \Contao\StringUtil::deserialize($objElement->grid_cols);
        } else {
            $cols = $objElement->grid_cols;
        }

        if (!\is_array($objElement->grid_gap)) {
            $gap = \Contao\StringUtil::deserialize($objElement->grid_gap);
        } else {
            $gap = $objElement->grid_gap;
        }

        // We don't need rows, but what's the point of a grid without cols ?
        if (!\is_array($cols)) {
            return [];
        }
        $arrClasses[] = 'd-grid';

        if (\WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $objElement->grid_mode) {
            $arrClasses[] = 'cols-autofit';
        } elseif (\WEM\GridBundle\Elements\GridStart::MODE_CUSTOM === $objElement->grid_mode) {
            foreach ($cols as $k => $col) {
                // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                if (0 === $k) {
                    $arrClasses[] = sprintf('cols-%d', $col['value']);
                } else {
                    if ('FE' === TL_MODE) {
                        if (0 !== (int) $col['value']) {
                            $arrClasses[] = sprintf('cols-%s-%d', $col['key'], $col['value']);
                        }
                    } else {
                        $arrClasses[] = sprintf('cols-%s-%d', $col['key'], $col['value']);
                    }
                }
            }

            if (\is_array($rows)) {
                foreach ($rows as $k => $row) {
                    // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                    if (0 === $k) {
                        $arrClasses[] = sprintf('rows-%d', $row['value']);
                    } else {
                        if ('FE' === TL_MODE) {
                            if (0 !== (int) $row['value']) {
                                $arrClasses[] = sprintf('rows-%s-%d', $row['key'], $row['value']);
                            }
                        } else {
                            $arrClasses[] = sprintf('rows-%s-%d', $row['key'], $row['value']);
                        }
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
     * Generate item classes, depending of the element.
     *
     * @param ContentModel|DatabaseResult $objElement [description]
     */
    public static function getItemClasses($objElement, ?bool $forForm = false): array
    {
        $arrClasses = [];
        if (\is_array($objElement->grid_cols)) {
            $cols = $objElement->grid_cols;
        } else {
            $cols = \Contao\StringUtil::deserialize($objElement->grid_cols);
        }

        $arrClasses[] = 'item-grid';

        // Setup special item rules
        $arrItemsClasses = [];
        if (null !== $objElement->grid_items) {
            if (!\is_array($objElement->grid_items)) {
                $items = \Contao\StringUtil::deserialize($objElement->grid_items);
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
     */
    public function fakeFirstGridElementMarkup(string $gridId): string
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-', implode(' ', $grid->getWrapperClasses()));

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-first-element">%s</div>', $additionnalCssClasses, $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridStart']);
    }

    /**
     * Returns a "fake" grid element to allow element to be placed at the end of the grid.
     */
    public function fakeLastGridElementMarkup(string $gridId): string
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        // $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : '';
        $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : str_replace('cols-', 'cols-span-', implode(' ', $grid->getWrapperClasses()));

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-last-element">%s</div>', $additionnalCssClasses, $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridEnd']);
    }

    /**
     * Returns a "fake" grid element to allow new elements to be added at the end of the grid.
     */
    public function fakeNewGridElementMarkup(string $gridId): string
    {
        $gop = GridOpenedManager::getInstance();

        $grid = $gop->getGridById($gridId);

        // $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? 'cols-span-all' : '';
        $additionnalCssClasses = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC === $grid->getMode() ? '' : '';

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="false" draggable="false" data-type="fake-new-element"><div class="item-new"></div></div>', $additionnalCssClasses);
    }
}
