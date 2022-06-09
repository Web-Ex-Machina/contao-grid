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
use Contao\Controller;
use Contao\Database\Result as DatabaseResult;
use Contao\DataContainer;
use WEM\GridBundle\Classes\GridStartManipulator;

/**
 * Function to centralize generic code to.
 */
class GridBuilder extends Controller
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
            $rows = deserialize($objElement->grid_rows);
        } else {
            $rows = $objElement->grid_rows;
        }

        if (!\is_array($objElement->grid_cols)) {
            $cols = deserialize($objElement->grid_cols);
        } else {
            $cols = $objElement->grid_cols;
        }

        if (!\is_array($objElement->grid_gap)) {
            $gap = deserialize($objElement->grid_gap);
        } else {
            $gap = $objElement->grid_gap;
        }

        // We don't need rows, but what's the point of a grid without cols ?
        if (!\is_array($cols)) {
            return [];
        }

        switch ($objElement->grid_preset) {
            case 'bs3':
                throw new \Exception(sprintf('Preset %s removed', $objElement->grid_preset));
                break;

            case 'bs4':
                $arrClasses[] = $objElement->grid_row_class;

                // In BS4, we need row class in the wrapper
                if (!\in_array('row', $arrClasses, true)) {
                    $arrClasses[] = 'row';
                }
                break;

            case 'cssgrid':
                $arrClasses[] = 'd-grid';

                foreach ($cols as $k => $col) {
                    // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                    if (0 === $k) {
                        $arrClasses[] = sprintf('cols-%d', $col['value']);
                    } else {
                        $arrClasses[] = sprintf('cols-%s-%d', $col['key'], $col['value']);
                    }
                }

                if (!\is_array($rows)) {
                    // $arrClasses = [];
                } else {
                    foreach ($rows as $k => $row) {
                        // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                        if (0 === $k) {
                            $arrClasses[] = sprintf('rows-%d', $row['value']);
                        } else {
                            $arrClasses[] = sprintf('rows-%s-%d', $row['key'], $row['value']);
                        }
                    }
                }

                if (\is_array($gap)) {
                    $arrClasses[] = sprintf('gap-%d%s', $gap['value'], '' !== $gap['unit'] ? sprintf('-%s', $gap['unit']) : '');
                }
                break;

            default:
                throw new \Exception(sprintf('Preset %s unknown', $objElement->grid_preset));
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
            $cols = deserialize($objElement->grid_cols);
        }

        switch ($objElement->grid_preset) {
            case 'bs3':
                throw new \Exception(sprintf('Preset %s removed', $objElement->grid_preset));
                break;

            case 'bs4':
                if (!$cols) {
                    $arrClasses = [];
                    break;
                }

                if (1 === \count($cols)) {
                    $arrClasses[] = sprintf('col-%d', 12 / $cols[0]['value']);
                } else {
                    foreach ($cols as $k => $col) {
                        $arrClasses[] = sprintf('col-%s-%d', $col['key'], 12 / $col['value']);
                    }
                }
                break;

            case 'cssgrid':
                $arrClasses[] = 'item-grid';
                break;

            default:
                throw new \Exception(sprintf('Preset %s unknown', $objElement->grid_preset));
        }

        // Setup special item rules
        $arrItemsClasses = [];
        if (null !== $objElement->grid_items) {
            if (!\is_array($objElement->grid_items)) {
                $items = deserialize($objElement->grid_items);
            } else {
                $items = $objElement->grid_items;
            }
            if (!$forForm) {
                foreach ($items as $itemId => $classes) {
                    if (\is_array($classes)) {
                        $items[$itemId] = trim(implode(' ', $classes));
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
     * @param int $gridId The grid's id
     */
    public static function fakeFirstGridElementMarkup(string $gridId): string
    {
        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake %s" dropable="true" draggable="false" data-type="fake-first-element">%s</div>', str_replace('cols-', 'cols-span-', implode(' ', $GLOBALS['WEM']['GRID'][$gridId]['wrapper_classes'])), $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridStart']);
    }

    /**
     * Returns a "fake" grid element to allow element to be placed at the end of the grid.
     */
    public static function fakeLastGridElementMarkup(): string
    {
        return sprintf('<div class="item-grid be_item_grid fake-helper be_item_grid_fake" dropable="true" draggable="false" data-type="fake-last-element">%s</div>', $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['placeToGridEnd']);
    }

    /**
     * Returns a "fake" grid element to allow new elements to be added at the end of the grid.
     */
    public static function fakeNewGridElementMarkup(): string
    {
        return '<div class="item-grid be_item_grid fake-helper be_item_grid_fake" dropable="false" draggable="false"><div class="item-new"></div></div>';
    }

    /**
     * Automaticly create a GridStop element when creating a GridStart element.
     */
    public function createGridStop(DataContainer $dc): void
    {
        if (null !== $dc->activeRecord && 'grid-start' === $dc->activeRecord->type) {
            $gridStarts = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-start']);
            $gridStops = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-stop']);

            if ($gridStarts > $gridStops) {
                $objElement = new ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = 'grid-stop';
                // $objElement->sorting = $dc->activeRecord->sorting + 64;
                $objElement->sorting = $dc->activeRecord->sorting + 1;
                $objElement->save();
            }
        }
    }

    public function includeJSCSS(): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        $this->createGridStop($dc);
        $objItem = ContentModel::findOneById($dc->activeRecord->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        // When submitting a grid with subgrids, all styles are saved in parent grid instead of each subgrids
        self::recalculateGridItemsByPidAndPtable((int) $dc->activeRecord->pid, $dc->activeRecord->ptable, true);
    }

    public function oncutCallback(DataContainer $dc): void
    {
        $objItem = ContentModel::findOneById($dc->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        self::recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function oncopyCallback(int $itemId, DataContainer $dc): void
    {
        $objItem = ContentModel::findOneById($itemId);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        // ugly fix to allow duplication of element in grid edition
        $objItem->tstamp = 0 !== (int) $objItem->tstamp ? $objItem->tstamp : time();
        $objItem->save();
        // end of ugly fix
        self::recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function ondeleteCallback(DataContainer $dc, int $undoItemId): void
    {
        if (!$dc->id) {
            return;
        }
        $objItem = ContentModel::findOneById($dc->id);
        if (!$objItem) {
            return;
        }
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        if ('grid-start' === $objItem->type) {
            $this->deleteClosestGridStopFromGridStart($objItem);
        }
        self::recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function deleteClosestGridStopFromGridStart(ContentModel $gridStart): void
    {
        $gridStop = ContentModel::findBy(['pid = ?', 'ptable = ?', 'type = ?', 'sorting > ?'], [$gridStart->pid, $gridStart->ptable, 'grid-stop', $gridStart->sorting], ['limit' => 1, 'order' => 'sorting ASC']);
        if ($gridStop) {
            $gridStop->delete();
        }
    }

    /**
     * Recalculate grid items by pid and ptable.
     *
     * @param int    $pid              The pid
     * @param string $ptable           The ptable
     * @param bool   $keepItemsClasses true to keep the item classes between grids
     */
    public static function recalculateGridItemsByPidAndPtable(int $pid, string $ptable, bool $keepItemsClasses = false): void
    {
        $objItems = ContentModel::findBy(['pid = ?', 'ptable = ?'], [$pid, $ptable], ['order' => 'sorting ASC']);
        $objItemsIdsToSkip = [];
        $itemsClasses = [];
        // first we keep track of all grid_items settings
        foreach ($objItems as $index => $objItem) {
            if ('grid-start' === $objItem->type) {
                $itemsClasses = $itemsClasses + (null !== $objItem->grid_items ? deserialize($objItem->grid_items) : []);
            }
        }
        foreach ($objItems as $index => $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }
            if ('grid-start' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, self::recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses, $keepItemsClasses));
            }
        }
    }

    /**
     * Recalculate elements inside a grid.
     *
     * @param ContentModel             $gridStart         The "grid-start" content element
     * @param array                    $objItemsIdsToSkip Array of content elements' ID to skip (not in the grid started by the current content element)
     * @param \Contao\Model\Collection $objItems          Array of all content elements sharing the same pid & ptable with the current content element
     * @param array                    $itemsClasses      items classes across grids
     *
     * @return array Array of content elements' ID to skip (for the next grid to not use the current content elements items)
     */
    protected static function recalculateGridItems(ContentModel $gridStart, array $objItemsIdsToSkip, \Contao\Model\Collection $objItems, array $itemsClasses, bool $keepItemsClasses): array
    {
        $gridItemsSave = null !== $gridStart->grid_items ? unserialize($gridStart->grid_items) : [];
        $gridStart->grid_items = serialize([]);
        $gsm = GridStartManipulator::create($gridStart);

        foreach ($objItems as $index => $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }
            if ('grid-stop' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;

                return $objItemsIdsToSkip;
            }
            if ('grid-start' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, self::recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses, $keepItemsClasses));
            }

            if (!$gsm->isItemInGrid($objItem)) {
                $gsm->setGridItemsSettingsForItem((int) $objItem->id,
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_COLS] ?? [],
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS] ?? [],
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES] ?? ''
                    );
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_COLS, $itemsClasses)) {
                    $gsm->setGridItemCols((int) $objItem->id, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_COLS]);
                }
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS, $itemsClasses)) {
                    $gsm->setGridItemRows((int) $objItem->id, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS]);
                }
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES, $itemsClasses)) {
                    $gsm->setGridItemsSettingsForItemAndPropertyAndResolution((int) $objItem->id, GridStartManipulator::PROPERTY_CLASSES, null, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES]);
                }
                $gridStart = $gsm->getGridStart();
                $gridStart->save();
                $gsm->setGridStart($gridStart);
            }
            $objItemsIdsToSkip[] = $objItem->id;
        }

        return $objItemsIdsToSkip;
    }
}
