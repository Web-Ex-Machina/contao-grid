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

namespace WEM\GridBundle\Classes;
use Contao\Controller;
use Contao\ContentModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\Image;
use WEM\GridBundle\Helper\GridBuilder;
/**
 * Grid Hooks.
 */
class Hooks extends Controller
{
    protected static $arrSkipContentTypes = ['grid-start', 'grid-stop'];

    /**
     * Hook getContentElement : Check if the element is in a Grid and wrap them.
     *
     * @param [ContentModel] $objElement [Content Element Model]
     * @param [String]       $strBuffer  [Content Template parsed]
     *
     * @return [String] [Content Template, untouched or adjusted]
     */
    public function wrapGridElements(ContentModel $objElement, $strBuffer)
    {
        // Skip elements we never want to wrap or if we are not in a grid
        if ((TL_MODE === 'BE' && 'edit' !== Input::get('act')) || null === $GLOBALS['WEM']['GRID'] || empty($GLOBALS['WEM']['GRID'])) {
            return $strBuffer;
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        $k = key($GLOBALS['WEM']['GRID']);
        $currentGridId = $k;
        reset($GLOBALS['WEM']['GRID']);

        // For each opened grid, we will add the elements into it
        foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
            if ($k !== $objElement->id) {
                if (!\array_key_exists('elements', $GLOBALS['WEM']['GRID'][$k])) {
                    $GLOBALS['WEM']['GRID'][$k]['elements'] = [];
                }
                $GLOBALS['WEM']['GRID'][$k]['elements'][] = $objElement->id;
                $currentGridId = $k;
            }
        }

        // We won't need this grid anymore so we pop the global grid array
        if ('grid-stop' === $objElement->type) {
            array_pop($GLOBALS['WEM']['GRID']);
        }

        // If we used grids elements, we had to adjust the behaviour
        if ('grid-start' === $objElement->type && true === $arrGrid['subgrid']) {
            // For nested grid - starts, we want to add only the start of the item wrapper
            // Retrieve the parent
            foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
                if (is_array($g['item_classes']['items']) && array_key_exists($objElement->id, $g['item_classes']['items'])) {
                    $arrGrid = $g;
                }
            }
            return sprintf(
                '<div class="%s %s %s be_subgrid" data-id="%s" data-type="%s" data-nb-cols="%s">%s%s%s',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $arrGrid['item_classes']['items'][$objElement->id.'_classes'] ?: '',
                $objElement->id,
                $objElement->type,
                !\is_array($objElement->grid_cols) ? deserialize($objElement->grid_cols)[0]['value'] : $objElement->grid_cols[0]['value'],
                TL_MODE === 'BE' && !Input::get('grid_preview') ? $this->getBackendActionsForGridContentElement($objElement,true) : '',
                $strBuffer,
                GridBuilder::fakeFirstGridElementMarkup((string) $currentGridId)
            );
        }
        if ('grid-stop' === $objElement->type && true === $arrGrid['subgrid']) {
            return sprintf(
                '%s<div data-id="%s" data-type="%s">%s</div></div>',
                GridBuilder::fakeLastGridElementMarkup(),
                $objElement->id,
                $objElement->type,
                $strBuffer
            );
        }
        if (!\in_array($objElement->type, static::$arrSkipContentTypes, true) && true === $arrGrid['subgrid']) {
            return sprintf(
                '<div class="%s %s %s be_subgrid_item" data-id="%s" data-type="%s">%s%s</div>',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $arrGrid['item_classes']['items'][$objElement->id.'_classes'] ?: '',
                $objElement->id,
                $objElement->type,
                TL_MODE === 'BE' && !Input::get('grid_preview') ? $this->getBackendActionsForContentElement($objElement,true) : '',
                $strBuffer
            );
        }
        if (!\in_array($objElement->type, static::$arrSkipContentTypes, true)) {
            return sprintf(
                '<div class="%s %s %s" data-id="%s" data-type="%s">%s%s</div>',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $arrGrid['item_classes']['items'][$objElement->id.'_classes'] ?: '',
                $objElement->id,
                $objElement->type,
                TL_MODE === 'BE' && !Input::get('grid_preview') ? $this->getBackendActionsForContentElement($objElement,true) : '',
                $strBuffer
            );
        }

        return $strBuffer;
    }

    public function getBackendActionsForContentElement(ContentModel $objElement, bool $withActions): string
    {
        if($withActions){
            $titleEdit = sprintf($GLOBALS['TL_LANG']['DCA']['edit'],$objElement->id);
            $titleDelete = sprintf($GLOBALS['TL_LANG']['DCA']['delete'],$objElement->id);
            $titleDrag = sprintf($GLOBALS['TL_LANG']['DCA']['drag'],$objElement->id);
            $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'],$objElement->id) : null;

            $buttons = sprintf('
                <a 
                href="contao?do=article&id=%s&table=tl_content&act=edit&popup=1&nb=1&amp;rt=%s" 
                title="%s" 
                onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">
                %s
                </a>',$objElement->id,REQUEST_TOKEN,StringUtil::specialchars($titleEdit),StringUtil::specialchars(str_replace("'", "\\'", $titleEdit)),Image::getHtml('edit.svg', $titleEdit));

            $buttons.= sprintf('<a href="contao?do=article&id=%s&table=tl_content&act=delete&popup=1&nb=1&amp;rt=%s" title="%s" onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()">%s</a>',$objElement->id,REQUEST_TOKEN,StringUtil::specialchars($titleDelete), $confirmDelete ,Image::getHtml('delete.svg', $titleDelete));

            $buttons.= sprintf('
                <a 
                href="#" 
                onClick="return false;"
                title="%s" 
                class="drag-handle">
                %s
                </a>',StringUtil::specialchars($titleDrag),Image::getHtml('drag.svg', $titleDrag));
        }
        return sprintf('<div class="item-actions">%s (ID %s)%s%s</div>',$objElement->type, $objElement->id,$withActions ? ' - ' : '',$withActions ? $buttons : '');
    }

    public function getBackendActionsForGridContentElement(\Contao\ContentModel $objElement, bool $withActions): string
    {
        if($withActions){
        $titleEdit = sprintf($GLOBALS['TL_LANG']['DCA']['edit'],$objElement->id);
        $titleDelete = sprintf($GLOBALS['TL_LANG']['DCA']['delete'],$objElement->id);
        $titleDrag = sprintf($GLOBALS['TL_LANG']['DCA']['drag'],$objElement->id);
        $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'],$objElement->id) : null;

        $buttons = sprintf('
            <a 
            href="contao?do=article&id=%s&table=tl_content&act=edit&nb=1&amp;rt=%s" 
            title="%s" 
            target="_blank">
            %s
            </a>',$objElement->id,REQUEST_TOKEN,StringUtil::specialchars($titleEdit),Image::getHtml('edit.svg', $titleEdit));

        $buttons.= sprintf('<a href="contao?do=article&id=%s&table=tl_content&act=delete&popup=1&nb=1&amp;rt=%s" title="%s" onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()">%s</a>',$objElement->id,REQUEST_TOKEN,StringUtil::specialchars($titleDelete), $confirmDelete ,Image::getHtml('delete.svg', $titleDelete));

        $buttons.= sprintf('
            <a 
            href="#" 
            onClick="return false;"
            title="%s" 
            class="drag-handle">
            %s
            </a>',StringUtil::specialchars($titleDrag),Image::getHtml('drag.svg', $titleDrag));
        }
        return sprintf('<div class="item-actions">%s (ID %s)%s%s</div>',$objElement->type, $objElement->id,$withActions ? ' - ' : '',$withActions ? $buttons : '');
    }
}
