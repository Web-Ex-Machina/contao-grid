<?php

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Helper;

/**
 * Function to centralize generic code to
 */
class GridBuilder extends \Controller
{
    /**
     * Generate wrapper classes, depending of the element
     * @param  \ContentModel $objElement [description]
     * @return [type]                    [description]
     */
    public static function getWrapperClasses($objElement)
    {
        $arrClasses = [];
        
        if (!is_array($objElement->grid_rows)) {
            $rows = unserialize($objElement->grid_rows);
        } else {
            $rows = $objElement->grid_rows;
        }

        if (!is_array($objElement->grid_cols)) {
            $cols = unserialize($objElement->grid_cols);
        } else {
            $cols = $objElement->grid_cols;
        }

        // We don't need rows, but what's the point of a grid without cols ?
        if (!is_array($cols)) {
            return [];
        }

        switch ($objElement->grid_preset) {
            case 'bs3':
                throw new \Exception(sprintf("Preset %s removed", $objElement->grid_preset));
                break;

            case 'bs4':
                $arrClasses[] = $objElement->grid_row_class;

                // In BS4, we need row class in the wrapper
                if (!in_array('row', $arrClasses)) {
                    $arrClasses[] = 'row';
                }
                break;

            case 'cssgrid':
                $arrClasses[] = "d-grid";

                foreach ($cols as $k => $col) {
                    // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                    if (0 == $k) {
                        $arrClasses[] = sprintf("cols-%d", $col['value']);
                    } else {
                        $arrClasses[] = sprintf("cols-%s-%d", $col['key'], $col['value']);
                    }
                }

                if (!is_array($rows)) {
                    $arrClasses = [];
                } else {
                    foreach ($rows as $k => $row) {
                        // Quickfix : we need the first col to be generic, no matter what is the breakpoint
                        if (0 == $k) {
                            $arrClasses[] = sprintf("rows-%d", $row['value']);
                        } else {
                            $arrClasses[] = sprintf("rows-%s-%d", $row['key'], $row['value']);
                        }
                    }
                }
                break;

            default:
                throw new \Exception(sprintf("Preset %s unknown", $objElement->grid_preset));
        }

        return $arrClasses;
    }

    /**
     * Generate item classes, depending of the element
     * @param  \ContentModel $objElement [description]
     * @return [type]                    [description]
     */
    public static function getItemClasses($objElement)
    {
        $arrClasses = [];
        if (is_array($objElement->grid_cols)) {
            $cols = $objElement->grid_cols;
        } else {
            $cols = unserialize($objElement->grid_cols);
        }

        switch ($objElement->grid_preset) {
            case 'bs3':
                throw new \Exception(sprintf("Preset %s removed", $objElement->grid_preset));
                break;

            case 'bs4':
                if (!$cols) {
                    $arrClasses = [];
                    break;
                }

                if (1 == count($cols)) {
                    $arrClasses[] = sprintf("col-%d", 12 / $cols[0]['value']);
                } else {
                    foreach ($cols as $k => $col) {
                        $arrClasses[] = sprintf("col-%s-%d", $col['key'], 12 / $col['value']);
                    }
                }
                break;

            case 'cssgrid':
                $arrClasses[] = 'item-grid';
                break;

            default:
                throw new \Exception(sprintf("Preset %s unknown", $objElement->grid_preset));
        }

        // Setup special item rules
        $arrItemsClasses = [];
        if (null !== $objElement->grid_items) {
            if (!is_array($objElement->grid_items)) {
                $items = unserialize($objElement->grid_items);
            } else {
                $items = $objElement->grid_items;
            }

            if (0 < count($items)) {
                $arrItemsClasses = $items;
            }
        }

        return ["all" => $arrClasses, "items" => $arrItemsClasses];
    }

    /**
     * Automaticly create a GridStop element when creating a GridStart element
     *
     * @param  DataContainer $dc
     *
     * @return
     */
    public function createGridStop($dc)
    {
        if (null != $dc->activeRecord && "grid-start" == $dc->activeRecord->type) {
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
                    if ("grid-stop" == $objDb->type) {
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
                $objElement->type = "grid-stop";
                $objElement->sorting = $dc->activeRecord->sorting + 64;
                $objElement->save();
            }
        }
    }
}
