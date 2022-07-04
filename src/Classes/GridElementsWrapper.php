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

namespace WEM\GridBundle\Classes;

use Contao\ContentModel;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\GridBundle\Helper\GridBuilder;

/**
 * Grid Hooks.
 */
class GridElementsWrapper
{
    /** @var TranslatorInterface */
    protected $translator;
    /** @var GridBuilder */
    protected $gridBuilder;
    /** @var GridCssClassesInheritance */
    protected $gridCssClassesInheritance;
    protected static $arrSkipContentTypes = ['grid-start', 'grid-stop'];

    public function __construct(
        TranslatorInterface $translator,
        GridBuilder $gridBuilder,
        GridCssClassesInheritance $gridCssClassesInheritance
    ) {
        $this->translator = $translator;
        $this->gridBuilder = $gridBuilder;
        $this->gridCssClassesInheritance = $gridCssClassesInheritance;
    }

    /**
     * Hook getContentElement : Check if the element is in a Grid and wrap them.
     *
     * @param [ContentModel] $objElement [Content Element Model]
     * @param [String]       $strBuffer  [Content Template parsed]
     * @param [String]       $do  The $_GET['do'] paramater
     *
     * @return [String] [Content Template, untouched or adjusted]
     */
    public function wrapGridElements(ContentModel $objElement, string $strBuffer, string $do): string
    {
        $gop = GridOpenedManager::getInstance();
        // Skip elements we never want to wrap or if we are not in a grid
        if ((TL_MODE === 'BE' && 'edit' !== Input::get('act')) || null === $gop->getLastOpenedGridId()) {
            return $strBuffer;
        }
        // Get the last open grid
        $openGrid = $gop->getLastOpenedGrid();
        $currentGridId = $gop->getLastOpenedGridId();

        // We won't need this grid anymore so we pop the global grid array
        if ('grid-stop' === $objElement->type) {
            $gop->closeLastOpenedGrid();
        }

        // If we used grids elements, we had to adjust the behaviour
        if ('grid-start' === $objElement->type && true === $openGrid->isSubGrid()) {
            $gop->openGrid($objElement);
            // For nested grid - starts, we want to add only the start of the item wrapper
            // Retrieve the parent
            $openGrid = $gop->getParentGrid($objElement);

            return $this->getSubGridStartHTMLMarkup($openGrid, $objElement, $currentGridId, $strBuffer, $do);
        }
        if ('grid-stop' === $objElement->type && true === $openGrid->isSubGrid()) {
            return $this->getGridStopHTMLMarkup($objElement, $strBuffer);
        }
        if (!\in_array($objElement->type, static::$arrSkipContentTypes, true)) {
            return $this->getGridElementHTMLMarkup($openGrid, $objElement, $currentGridId, $strBuffer, $do);
        }

        return $strBuffer;
    }

    /**
     * Returns the HTML code to display a buttons bar for a content element inside a grid.
     *
     * @param ContentModel $objElement  The content element
     * @param string       $do          The $_GET['do'] value
     * @param bool         $withActions Display actions buttons
     */
    public function getBackendActionsForContentElement(ContentModel $objElement, string $do, bool $withActions): string
    {
        if ($withActions) {
            $titleEdit = $this->translator->trans('DCA.edit', [$objElement->id], 'contao_default');
            $titleCopy = $this->translator->trans('DCA.copy', [$objElement->id], 'contao_default');
            $titleDelete = $this->translator->trans('DCA.delete', [$objElement->id], 'contao_default');
            $titleDrag = $this->translator->trans('DCA.drag', [$objElement->id], 'contao_default');
            $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? $this->translator->trans('MSC.deleteConfirm', [$objElement->id], 'contao_default') : null;

            $buttons = '';

            if ('grid-item-empty' !== $objElement->type) {
                $buttons .= sprintf('
                <a
                href="contao?do=%s&id=%s&table=tl_content&act=edit&popup=1&nb=1&amp;rt=%s"
                title="%s"
                onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">
                %s
                </a>', $do, $objElement->id, REQUEST_TOKEN, StringUtil::specialchars($titleEdit), StringUtil::specialchars(str_replace("'", "\\'", $titleEdit)), Image::getHtml('edit.svg', $titleEdit));
            }
            $buttons .= sprintf('
                <a class="item-copy"
                href="#"
                data-element-id="%s"
                title="%s"
                >
                %s
                </a>', $objElement->id, StringUtil::specialchars($titleCopy), Image::getHtml('copy.svg', $titleCopy));

            $buttons .= sprintf('
                <a class="item-delete"
                href="#"
                data-element-id="%s"
                title="%s"
                onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()"
                >
                %s
                </a>', $objElement->id, StringUtil::specialchars($titleDelete), $confirmDelete, Image::getHtml('delete.svg', $titleDelete));

            $buttons .= sprintf('
                <a
                href="#"
                onClick="return false;"
                title="%s"
                class="drag-handle">
                %s
                </a>', StringUtil::specialchars($titleDrag), Image::getHtml('drag.svg', $titleDrag));
        }

        return sprintf('<div class="item-actions">%s (ID %s)%s%s</div>', $GLOBALS['TL_LANG']['CTE'][$objElement->type][0], $objElement->id, $withActions ? ' - ' : '', $withActions ? $buttons : '');
    }

    /**
     * Returns the HTML code to display a buttons bar for a grid-start content element inside a grid.
     *
     * @param ContentModel $objElement  The content element
     * @param string       $do          The $_GET['do'] value
     * @param bool         $withActions Display actions buttons
     */
    public function getBackendActionsForGridStartContentElement(ContentModel $objElement, string $do, bool $withActions): string
    {
        if ($withActions) {
            $titleEdit = $this->translator->trans('DCA.edit', [$objElement->id], 'contao_default');
            $titleCopy = $this->translator->trans('DCA.copy', [$objElement->id], 'contao_default');
            $titleDelete = $this->translator->trans('DCA.delete', [$objElement->id], 'contao_default');
            $titleDrag = $this->translator->trans('DCA.drag', [$objElement->id], 'contao_default');
            $confirmDelete = isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']) ? $this->translator->trans('MSC.deleteConfirm', [$objElement->id], 'contao_default') : null;

            $buttons = sprintf('
                <a
                href="contao?do=%s&id=%s&table=tl_content&act=edit&nb=1&amp;rt=%s"
                title="%s"
                target="_blank">
                %s
                </a>', $do, $objElement->id, REQUEST_TOKEN, StringUtil::specialchars($titleEdit), Image::getHtml('edit.svg', $titleEdit));

            $buttons .= sprintf('
                <a class="item-delete"
                href="#"
                data-element-id="%s"
                title="%s"
                onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()"
                >
                %s
                </a>', $objElement->id, StringUtil::specialchars($titleDelete), $confirmDelete, Image::getHtml('delete.svg', $titleDelete));

            $buttons .= sprintf('
                <a
                href="#"
                onClick="return false;"
                title="%s"
                class="drag-handle">
                %s
                </a>', StringUtil::specialchars($titleDrag), Image::getHtml('drag.svg', $titleDrag));
        }

        return sprintf('<div class="item-actions">%s (ID %s)%s%s</div>', $objElement->type, $objElement->id, $withActions ? ' - ' : '', $withActions ? $buttons : '');
    }

    protected function getSubGridStartHTMLMarkup(GridOpened $openGrid, ContentModel $objElement, string $currentGridId, string $strBuffer, string $do): string
    {
        if (TL_MODE === 'BE') {
            return sprintf(
                '<div class="%s %s %s %s be_subgrid" data-id="%s" data-type="%s" data-nb-cols="%s">%s%s%s',
                implode(' ', $openGrid->getItemClassesForAllResolution()),
                $openGrid->getItemClassesColsForItemId($objElement->id) ?: '',
                $openGrid->getItemClassesRowsForItemId($objElement->id) ?: '',
                $openGrid->getItemClassesClassesForItemId($objElement->id) ?: '',
                $objElement->id,
                $objElement->type,
                !\is_array($objElement->grid_cols) ? deserialize($objElement->grid_cols)[0]['value'] : $objElement->grid_cols[0]['value'],
                !Input::get('grid_preview') ? $this->getBackendActionsForGridStartContentElement($objElement, $do, true) : '',
                $strBuffer,
                !Input::get('grid_preview') ? $this->gridBuilder->fakeFirstGridElementMarkup((string) $currentGridId) : ''
            );
        }

        return sprintf(
            '<div class="%s %s %s %s">%s',
            implode(' ', $openGrid->getItemClassesForAllResolution()),
            $this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesColsForItemId($objElement->id) ?: ''),
            $this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesRowsForItemId($objElement->id) ?: ''),
            $openGrid->getItemClassesClassesForItemId($objElement->id) ?: '',
            $strBuffer
        );
    }

    protected function getGridStopHTMLMarkup(ContentModel $objElement, string $strBuffer): string
    {
        if (TL_MODE === 'BE') {
            return sprintf(
                '%s<div data-id="%s" data-type="%s">%s</div></div>',
               !Input::get('grid_preview') ? $this->gridBuilder->fakeLastGridElementMarkup() : '',
                $objElement->id,
                $objElement->type,
                $strBuffer
            );
        }

        return sprintf(
            '<div>%s</div></div>',
            $strBuffer
        );
    }

    protected function getGridElementHTMLMarkup(GridOpened $openGrid, ContentModel $objElement, string $currentGridId, string $strBuffer, string $do): string
    {
        if (TL_MODE === 'BE') {
            return sprintf(
                '<div class="%s %s %s %s %s %s" data-id="%s" data-type="%s">%s%s</div>',
                implode(' ', $openGrid->getItemClassesForAllResolution()),
                $openGrid->getItemClassesColsForItemId($objElement->id) ?: '',
                $openGrid->getItemClassesRowsForItemId($objElement->id) ?: '',
                $openGrid->getItemClassesClassesForItemId($objElement->id) ?: '',
                true === $openGrid->isSubGrid() ? 'be_subgrid_item' : '',
                'grid-item-empty' === $objElement->type ? 'be_grid_item_empty' : '',
                $objElement->id,
                $objElement->type,
                !Input::get('grid_preview') ? $this->getBackendActionsForContentElement($objElement, $do, true) : '',
                $strBuffer
            );
        }

        return sprintf(
            '<div class="%s %s %s %s">%s</div>',
            implode(' ', $openGrid->getItemClassesForAllResolution()),
            $this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesColsForItemId($objElement->id) ?: ''),
            $this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesRowsForItemId($objElement->id) ?: ''),
            $openGrid->getItemClassesClassesForItemId($objElement->id) ?: '',
            $strBuffer
        );
    }
}
