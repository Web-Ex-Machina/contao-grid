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
        // if(TL_MODE === 'BE' && $objElement->type == "grid-start"){
        //     return $this->prepareGridElementsInsideGridStartBEElement($objElement, $strBuffer);
        // }
        // Skip elements we never want to wrap or if we are not in a grid
        if ((TL_MODE === 'BE' && 'edit' !== Input::get('act')) || null === $GLOBALS['WEM']['GRID'] || empty($GLOBALS['WEM']['GRID'])) {
            return $strBuffer;
        }

        // Get the last open grid
        $arrGrid = end($GLOBALS['WEM']['GRID']);
        $k = key($GLOBALS['WEM']['GRID']);
        reset($GLOBALS['WEM']['GRID']);

        // For each opened grid, we will add the elements into it
        foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
            if ($k !== $objElement->id) {
                if (!\array_key_exists('elements', $GLOBALS['WEM']['GRID'][$k])) {
                    $GLOBALS['WEM']['GRID'][$k]['elements'] = [];
                }
                $GLOBALS['WEM']['GRID'][$k]['elements'][] = $objElement->id;
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
                '<div class="%s %s %s" data-id="%s" data-type="%s">%s%s',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $arrGrid['item_classes']['items'][$objElement->id.'_classes'] ?: '',
                $objElement->id,
                $objElement->type,
                TL_MODE === 'BE' ? $this->getBackendActionsForGridContentElement($objElement) : '',
                $strBuffer
            );
        }
        if ('grid-stop' === $objElement->type && true === $arrGrid['subgrid']) {
            return sprintf(
                '<div data-id="%s" data-type="%s">%s</div></div>',
                $objElement->id,
                $objElement->type,
                $strBuffer
            );
        }
        if (!\in_array($objElement->type, static::$arrSkipContentTypes, true) && true === $arrGrid['subgrid']) {
            return sprintf(
                '<div class="%s %s %s" data-id="%s" data-type="%s">%s</div>',
                implode(' ', $arrGrid['item_classes']['all']),
                $arrGrid['item_classes']['items'][$objElement->id] ?: '',
                $arrGrid['item_classes']['items'][$objElement->id.'_classes'] ?: '',
                $objElement->id,
                $objElement->type,
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
                TL_MODE === 'BE' ? $this->getBackendActionsForContentElement($objElement) : '',
                $strBuffer
            );
        }

        return $strBuffer;
    }

    public function getBackendActionsForContentElement(\Contao\ContentModel $objElement): string
    {
        $titleEdit = sprintf($GLOBALS['TL_LANG']['DCA']['edit'],$objElement->id);
        $titleDelete = sprintf($GLOBALS['TL_LANG']['DCA']['delete'],$objElement->id);
        $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'],$objElement->id) : null;

        $buttons = sprintf('
            <a 
            href="contao?do=article&id=%s&table=tl_content&act=edit&popup=1&nb=1&amp;rt=%s" 
            title="%s" 
            onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">
            %s
            </a>',$objElement->id,REQUEST_TOKEN,\Contao\StringUtil::specialchars($titleEdit),\Contao\StringUtil::specialchars(str_replace("'", "\\'", $titleEdit)),\Contao\Image::getHtml('edit.svg', $titleEdit));

        $buttons.= sprintf('<a href="contao?do=article&id=%s&table=tl_content&act=delete&popup=1&nb=1&amp;rt=%s" title="%s" onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()">%s</a>',$objElement->id,REQUEST_TOKEN,\Contao\StringUtil::specialchars($titleDelete), $confirmDelete ,\Contao\Image::getHtml('delete.svg', $titleDelete));

        return sprintf('<div class="item-actions">%s (ID %s) - %s</div>',$objElement->type, $objElement->id,$buttons);
    }

    public function getBackendActionsForGridContentElement(\Contao\ContentModel $objElement): string
    {
        $titleEdit = sprintf($GLOBALS['TL_LANG']['DCA']['edit'],$objElement->id);
        $titleDelete = sprintf($GLOBALS['TL_LANG']['DCA']['delete'],$objElement->id);
        $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'],$objElement->id) : null;

        $buttons = sprintf('
            <a 
            href="contao?do=article&id=%s&table=tl_content&act=edit&nb=1&amp;rt=%s" 
            title="%s" 
            target="_blank">
            %s
            </a>',$objElement->id,REQUEST_TOKEN,\Contao\StringUtil::specialchars($titleEdit),\Contao\Image::getHtml('edit.svg', $titleEdit));

        $buttons.= sprintf('<a href="contao?do=article&id=%s&table=tl_content&act=delete&popup=1&nb=1&amp;rt=%s" title="%s" onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()">%s</a>',$objElement->id,REQUEST_TOKEN,\Contao\StringUtil::specialchars($titleDelete), $confirmDelete ,\Contao\Image::getHtml('delete.svg', $titleDelete));

        return sprintf('<div class="item-actions">%s (ID %s) - %s</div>',$objElement->type, $objElement->id,$buttons);
    }

    public function prepareGridElementsInsideGridStartBEElement(ContentModel $objElement, $strBuffer): string
    {
        // return $strBuffer;
        return $strBuffer . $this->prepareGrid($objElement);
    }

    protected function prepareGrid(ContentModel $objElement): string
    {
        $gridCols = unserialize($objElement->grid_cols ?? '') ?? [];
        $gridItems = unserialize($objElement->grid_items ?? '') ?? [];

        $strGrid = $this->prepareGridStart($objElement, $gridCols);
        $strGrid.= $this->prepareGridItems($gridItems);
        $strGrid.= $this->prepareGridStop();

        return $strGrid;
    }

    protected function prepareGridStart(ContentModel $objElement,array $gridCols): string
    {
        $strGrid = '<div class="d-grid';
        foreach($gridCols as $gridCol){
            if("all" === $gridCol['key']){
                $strGrid.=sprintf(' cols-%s',$gridCol['value']);
            }else{
                $strGrid.=sprintf(' cols-%s-%s',$gridCol['key'],$gridCol['value']);
            }
        }

        $strGrid.= '" data-grid-item="'.$objElement->id.'">';

        return $strGrid;
    }

    protected function prepareGridStop(): string
    {
        return '</div>';
    }

    protected function prepareGridItems(array $gridItems): string
    {
        $strGrid = '';
        $strGridNested = '';
        $strGridNestedClasses = '';
        $gridItemsKeys = array_keys($gridItems) ?? [];
        for($i = 0; $i < count($gridItemsKeys)-1; $i = $i +2){
            $itemId = $gridItemsKeys[$i];

            $objItem = \Contao\ContentModel::findById($itemId);
            if($objItem->type === "grid-start"){
                $strGridNestedClasses = $gridItems[$gridItemsKeys[$i+1]];
                $strGridNested = sprintf('<div data-item="%s"></div>', $itemId);
            }elseif($objItem->type === "grid-stop"){
                $itemClasses = $gridItems[$gridItemsKeys[$i+1]];
                $strGrid.=sprintf('<div class="%s">%s<div data-item="%s"></div></div>', $strGridNestedClasses, $strGridNested,$itemId);
            }else{
                $itemClasses = $gridItems[$gridItemsKeys[$i+1]];
                $strGrid.=sprintf('<div data-item="%s" class="%s"></div>', $itemId, $itemClasses);
            }

        }

        return $strGrid;
    }
}
