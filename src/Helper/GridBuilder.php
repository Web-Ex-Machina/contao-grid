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

namespace WEM\GridBundle\Helper;

use Exception;

/**
 * Function to centralize generic code to.
 */
class GridBuilder extends \Controller
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
                break;

            default:
                throw new \Exception(sprintf('Preset %s unknown', $objElement->grid_preset));
        }

        return $arrClasses;
    }

    /**
     * Generate item classes, depending of the element.
     *
     * @param \ContentModel $objElement [description]
     *
     * @return [type] [description]
     */
    public static function getItemClasses($objElement)
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

            if (0 < \count($items)) {
                $arrItemsClasses = $items;
            }
        }

        return ['all' => $arrClasses, 'items' => $arrItemsClasses];
    }

    /**
     * Automaticly create a GridStop element when creating a GridStart element.
     *
     * @param DataContainer $dc
     *
     * @return
     */
    public function createGridStop($dc)
    {
        if (null !== $dc->activeRecord && 'grid-start' === $dc->activeRecord->type) {
            // Try to fetch the really next grid stop element
            // $strSQL = sprintf(
            //     "SELECT type FROM tl_content WHERE pid = %s AND ptable = '%s' AND sorting > %s ORDER BY sorting ASC",
            //     $dc->activeRecord->pid,
            //     $dc->activeRecord->ptable,
            //     $dc->activeRecord->sorting
            // );

            // $objDb = \Database::getInstance()->prepare($strSQL)->execute();

            // We'll check every other elements, if we don't find a "grid-stop" element, we have to create one
            // $blnCreate = true;
            // if ($objDb && 0 < $objDb->count()) {
            //     while ($objDb->next()) {
            //         if ('grid-stop' === $objDb->type) {
            //             $blnCreate = false;
            //             break;
            //         }
            //     }
            // }
            $gridStarts = \Contao\ContentModel::countBy(['pid = ?','ptable = ?','type = ?'],[$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-start']);
            $gridStops = \Contao\ContentModel::countBy(['pid = ?','ptable = ?','type = ?'],[$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-stop']);

            if($gridStarts > $gridStops){
                $objElement = new \Contao\ContentModel();
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

    public function includeJSCSS(){
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgrid/js/backend-tl_content-list.js';
    }

    public function oncutCallback(\Contao\DataContainer $dc): void
    {
        $objItem = \Contao\ContentModel::findOneById($dc->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        $this->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function oncopyCallback(int $itemId, \Contao\DataContainer $dc): void
    {
        $objItem = \Contao\ContentModel::findOneById($itemId);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        $this->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function ondeleteCallback(\Contao\DataContainer $dc, int $undoItemId): void
    {
        $objItem = \Contao\ContentModel::findOneById($dc->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        if('grid-start' == $objItem->type){
            $this->deleteClosestGridStopFromGridStart($objItem);
        }
        $this->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function deleteClosestGridStopFromGridStart(\Contao\ContentModel $gridStart)
    {
        $gridStop = \Contao\ContentModel::findBy(['pid = ?','ptable = ?','type = ?','sorting > ?'],[$gridStart->pid, $gridStart->ptable, 'grid-stop', $gridStart->sorting],['limit'=>1,'order'=>'sorting ASC']);
        if($gridStop){
            $gridStop->delete();
        }
    }

    public function recalculateGridItemsByPidAndPtable(int $pid, string $ptable){
        $objItems = \Contao\ContentModel::findBy(['pid = ?','ptable = ?'],[$pid, $ptable],['order'=>'sorting ASC']);
        $objItemsIdsToSkip = [];
        foreach($objItems as $index => $objItem){
            if(in_array($objItem->id, $objItemsIdsToSkip)){
                continue;
            }
            if('grid-start' === $objItem->type){
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems));
            }
        }
    }

    protected function recalculateGridItems(\Contao\ContentModel $gridStart, array $objItemsIdsToSkip, \Contao\Model\Collection $objItems): array
    {
        $gridItems = []; // reset grid items
        $gridItemsSave = null !== $gridStart->grid_items ? unserialize($gridStart->grid_items) : [];

        foreach($objItems as $index => $objItem){
            if(in_array($objItem->id, $objItemsIdsToSkip)){
                continue;
            }
            if('grid-start' === $objItem->type){
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems));
                if(!in_array($objItem->id,array_keys($gridItems))){
                    $gridItems[$objItem->id] = "";
                    $gridItems[$objItem->id.'_classes'] = array_key_exists($objItem->id.'_classes', $gridItemsSave) ? $gridItemsSave[$objItem->id.'_classes'] : "";
                    $gridStart->grid_items = serialize($gridItems);
                    $gridStart->save();
                }
                $objItemsIdsToSkip[] = $objItem->id;
            }elseif('grid-stop' === $objItem->type){
                $objItemsIdsToSkip[] = $objItem->id;
                return $objItemsIdsToSkip;
            }else{
                if(!in_array($objItem->id,array_keys($gridItems))){
                    $gridItems[$objItem->id] = "";
                    $gridItems[$objItem->id.'_classes'] = array_key_exists($objItem->id.'_classes', $gridItemsSave) ? $gridItemsSave[$objItem->id.'_classes'] : "";
                    $gridStart->grid_items = serialize($gridItems);
                    $gridStart->save();
                }
                $objItemsIdsToSkip[] = $objItem->id;
            }
        }
        return $objItemsIdsToSkip;
    }
}
