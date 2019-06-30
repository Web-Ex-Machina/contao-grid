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
        if (null === $GLOBALS['WEM']['GRID'] || in_array($objElement->type, static::$arrSkipContentTypes)) {
            return $strBuffer;
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        $k = key($GLOBALS['WEM']['GRID']);
        reset($GLOBALS['WEM']['GRID']);

        // If there is no grid, just return the buffer
        if (!$arrGrid) {
            return $strBuffer;
        }

        $GLOBALS['WEM']['GRID'][$k]["elements"][] = $objElement->id;

        return sprintf(
            '<div class="%s %s">%s</div>',
            implode(' ', $arrGrid['item_classes']['all']),
            $arrGrid['item_classes']['items'][$objElement->id] ?: '',
            $strBuffer
        );
    }

    /**
     * Hook modifyFrontendPage : Check if we have grid-start // grid-stop elements
     * without contents and remove them if yes(they create blank spaces we don't
     * want)
     *
     * @param [String] $strBuffer [Page HTML]
     * @param [templateName] $strTemplate [Template used]
     *
     * @return [String] [Page HTML, untouched or adjusted]
     */
    public function clearEmptyGridWrappers($strBuffer, $strTemplate)
    {
        $regx = '/<div(.*)class="ce_grid-start(.*)>(.*\n.*)<\/div>/';
        preg_match_all($regx, $strBuffer, $wrappers, PREG_SET_ORDER);

        //dump($wrappers);

        // Remove opening and closing grid comments
        //$strBuffer = preg_replace('/<!--GridStart(\d{1,45})-->/', "", $strBuffer);
        //$strBuffer = preg_replace('/<!--GridStop(\d{1,45})-->/', "", $strBuffer);

        return $strBuffer;
    }
}
