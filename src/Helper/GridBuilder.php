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

        // switch ($objElement->grid_preset) {
        //     case 'bs3':
        //         throw new \Exception(sprintf('Preset %s removed', $objElement->grid_preset));
        //         break;

        //     case 'bs4':
        //         $arrClasses[] = $objElement->grid_row_class;

        //         // In BS4, we need row class in the wrapper
        //         if (!\in_array('row', $arrClasses, true)) {
        //             $arrClasses[] = 'row';
        //         }
        //         break;

        //     case 'cssgrid':
        $arrClasses[] = 'd-grid';

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

        if (\is_array($gap)) {
            $arrClasses[] = sprintf('gap-%d%s', $gap['value'], '' !== $gap['unit'] ? sprintf('-%s', $gap['unit']) : '');
        }
        // break;

        //     default:
        //         throw new \Exception(sprintf('Preset %s unknown', $objElement->grid_preset));
        // }

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

        // switch ($objElement->grid_preset) {
        //     case 'bs3':
        //         throw new \Exception(sprintf('Preset %s removed', $objElement->grid_preset));
        //         break;

        //     case 'bs4':
        //         if (!$cols) {
        //             $arrClasses = [];
        //             break;
        //         }

        //         if (1 === \count($cols)) {
        //             $arrClasses[] = sprintf('col-%d', 12 / $cols[0]['value']);
        //         } else {
        //             foreach ($cols as $k => $col) {
        //                 $arrClasses[] = sprintf('col-%s-%d', $col['key'], 12 / $col['value']);
        //             }
        //         }
        //         break;

        //     case 'cssgrid':
        $arrClasses[] = 'item-grid';
        //         break;

        //     default:
        //         throw new \Exception(sprintf('Preset %s unknown', $objElement->grid_preset));
        // }

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

        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-first-element">%s</div>', str_replace('cols-', 'cols-span-', implode(' ', $gop->getGridById($gridId)->getWrapperClasses())), $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridStart']);
    }

    /**
     * Returns a "fake" grid element to allow element to be placed at the end of the grid.
     */
    public function fakeLastGridElementMarkup(): string
    {
        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake" dropable="true" draggable="false" data-type="fake-last-element">%s</div>', $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridEnd']);
    }

    /**
     * Returns a "fake" grid element to allow new elements to be added at the end of the grid.
     */
    public function fakeNewGridElementMarkup(): string
    {
        return '<div class="item-grid be_item_grid fake-helper be_item_grid_fake" dropable="false" draggable="false"><div class="item-new"></div></div>';
    }
}
