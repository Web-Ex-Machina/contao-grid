<?php

/**
 * Grid Bundle for Contao Open Source CMS
 *
 * Copyright (c) 2019 Web ex Machina
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */

namespace WEM\GridBundle\Classes;

/**
 * Grid Hooks
 */
class Hooks extends \Controller
{
    protected static $arrSkipContentTypes = ['grid-start', 'grid-stop'];
    
    /**
     * Hook getContentElement : Check if the element is in a Grid and wrap them
     *
     * @param [ContentModel] $objElement [Content Element Model]
     * @param [String]       $strBuffer  [Content Template parsed]
     *
     * @return [String] [Content Template, untouched or adjusted]
     */
    public function wrapGridElements(\ContentModel $objElement, $strBuffer)
    {
        // Skip elements we never want to wrap or if we are not in a grid
        if ((TL_MODE == "BE" && 'edit' != \Input::get('act')) || null === $GLOBALS['WEM']['GRID'] || empty($GLOBALS['WEM']['GRID'])) {
            return $strBuffer;
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        $k = key($GLOBALS['WEM']['GRID']);
        reset($GLOBALS['WEM']['GRID']);

        // For each opened grid, we will add the elements into it
        foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
            if ($k != $objElement->id) {
                if (!array_key_exists('elements', $GLOBALS['WEM']['GRID'][$k])) {
                    $GLOBALS['WEM']['GRID'][$k]["elements"] = [];
                }
                $GLOBALS['WEM']['GRID'][$k]["elements"][] = $objElement->id;
            }
        }

        // We won't need this grid anymore so we pop the global grid array
        if ("grid-stop" == $objElement->type) {
            array_pop($GLOBALS['WEM']['GRID']);
        }

        // If we used grids elements, we had to adjust the behaviour
        if ("grid-start" == $objElement->type && true === $arrGrid['subgrid']) {
            // For nested grid - starts, we want to add only the start of the item wrapper
            return sprintf(
                '<div class="%s %s">%s',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $strBuffer
            );
        } elseif ("grid-stop" == $objElement->type && true === $arrGrid['subgrid']) {
            return sprintf(
                '%s</div>',
                $strBuffer
            );
        } elseif (!in_array($objElement->type, static::$arrSkipContentTypes)) {
            return sprintf(
                '<div class="%s %s">%s</div>',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $strBuffer
            );
        } else {
            return $strBuffer;
        }
    }
}
