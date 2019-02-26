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
     * getContentElement Hook : Check if the element is in a Grid and wrap them
     */
    public function wrapGridElements(\ContentModel $objElement, $strBuffer)
    {
        // Skip elements we never want to wrap or if we are not in a grid
        if (null === $GLOBALS['WEM']['GRID'] || in_array($objElement->type, static::$arrSkipContentTypes)) {
            return $strBuffer;
        }

        return sprintf(
            '<div class="%s %s">%s</div>',
            implode(' ', $GLOBALS['WEM']['GRID']['item_classes']['all']),
            $GLOBALS['WEM']['GRID']['item_classes']['items'][$objElement->id] ?: '',
            $strBuffer
        );
    }
}
