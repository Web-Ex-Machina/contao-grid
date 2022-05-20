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
            $strSQL = sprintf(
                "SELECT type FROM tl_content WHERE pid = %s AND ptable = '%s' AND sorting > %s ORDER BY sorting ASC",
                $dc->activeRecord->pid,
                $dc->activeRecord->ptable,
                $dc->activeRecord->sorting
            );

            $objDb = \Database::getInstance()->prepare($strSQL)->execute();

            // We'll check every other elements, if we don't find a "grid-stop" element, we have to create one
            $blnCreate = true;
            if ($objDb && 0 < $objDb->count()) {
                while ($objDb->next()) {
                    if ('grid-stop' === $objDb->type) {
                        $blnCreate = false;
                        break;
                    }
                }
            }

            if ($blnCreate) {
                $objElement = new \ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = 'grid-stop';
                $objElement->sorting = $dc->activeRecord->sorting + 64;
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
        $this->removeItemFromGridIfAny($objItem);
        $this->addItemToGridIfAny($dc->id, $dc);
    }


    public function oncopyCallback(int $itemId, \Contao\DataContainer $dc): void
    {
        $objItem = \Contao\ContentModel::findOneById($itemId);
        $this->removeItemFromGridIfAny($objItem);
        $this->addItemToGridIfAny($objItem);
    }

    public function removeItemFromGridIfAny($objItem): void
    {
        // find the item pid
        // find all the grid-start
        // for each, check if it conains a reference to the item id
        // if so, remove that reference
        $colGridStarts = \Contao\ContentModel::findBy(['pid = ?','ptable = ?','type = ?'],[$objItem->pid, $objItem->ptable,'grid-start']);
        if($colGridStarts){
            while($colGridStarts->next()){
                $gridItems = null !== $colGridStarts->current->grid_items ? unserialize($colGridStarts->current->grid_items) : [];
                if(!empty($colGridStarts->current->grid_items)){
                throw new Exception($colGridStarts->current->grid_items);
            }
                
                if(in_array($objItem->id,array_keys($gridItems))){
                    unset($gridItems[$objItem->id]);
                    unset($gridItems[$objItem->id.'_classes']);
                    $colGridStarts->current->grid_items = serialize($gridItems);
                    $colGridStarts->current->save();
                    throw new Exception('ohooooooooooo');
                }
            }
        }else{

                    throw new Exception('NO GRID START HERE');
        }
    }

    public function addItemToGridIfAny($objItem): void
    {
        // find the item pid
        // find the item sorting
        // find the closest grid-start with inferior sorting
        // find the closest grid-stop with inferior sorting
        // if grid-start.sorting > grid-stop.sorting, it means the grid is still opened (fake, nested grid babay)
        // add the item to the grid-start.grid_items
        // $objItem = $dc->activeRecord;

        $closestGridStart = \Contao\ContentModel::findBy(['pid = ?','ptable = ?','type = ?','sorting < ?'],[$objItem->pid, $objItem->ptable,'grid-start', $objItem->sorting],['limit'=>1,'order'=>'sorting DESC']);
        if(!$closestGridStart){
            return;
        }
        $closestGridStop = \Contao\ContentModel::findBy(['pid = ?','ptable = ?','type = ?','sorting < ?'],[$objItem->pid, $objItem->ptable,'grid-stop', $objItem->sorting],['limit'=>1,'order'=>'sorting DESC']);

        if(null !== $closestGridStop && (int) $closestGridStop->sorting > (int) $closestGridStart->sorting){
            return;
        }

        $gridItems = null !== $closestGridStart->grid_items ? unserialize($closestGridStart->grid_items) : [];
        if(!in_array($objItem->id,array_keys($gridItems))){
            $gridItems[$objItem->id] = "";
            $gridItems[$objItem->id.'_classes'] = "";
            $closestGridStart->grid_items = serialize($gridItems);
            $closestGridStart->save();
        }
    }


}
