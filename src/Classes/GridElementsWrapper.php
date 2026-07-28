<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
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
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\GridBundle\Elements\GridItemEmpty;
use WEM\GridBundle\Elements\GridStart;
use WEM\GridBundle\Helper\GridBuilder;

/**
 * Grid Hooks.
 */
class GridElementsWrapper
{
    protected TranslatorInterface $translator;

    protected GridBuilder $gridBuilder;

    protected GridCssClassesInheritance $gridCssClassesInheritance;

    protected static array $arrSkipContentTypes = [GridStart::ELEMENT_TYPE];

    public function __construct(
        TranslatorInterface $translator,
        GridBuilder $gridBuilder,
        GridCssClassesInheritance $gridCssClassesInheritance,
    ) {
        $this->translator = $translator;
        $this->gridBuilder = $gridBuilder;
        $this->gridCssClassesInheritance = $gridCssClassesInheritance;
    }

    /**
     * Hook getContentElement : Check if the element is in a Grid and wrap them.
     *
     * @param ContentModel $objElement Content Element Model
     * @param string       $strBuffer  Content Template parsed
     * @param string       $do         The $_GET['do'] paramater
     *
     * @return string Content Template, untouched or adjusted
     */
    public function wrapGridElements(ContentModel $objElement, string $strBuffer, string $do): string
    {
        $gop = GridOpenedManager::getInstance();

        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        
        // Skip elements we never want to wrap or if we are not in a grid
        if (($scopeMatcher->isBackend() && 'edit' !== Input::get('act')) || null === $gop->getLastOpenedGridId()) {
            return $strBuffer;
        }

        // If item parent is not a grid, return the item
        $objParent = ContentModel::findOneById($objElement->pid);

        if (!$objParent || GridStart::ELEMENT_TYPE !== $objParent->type) {
            return $strBuffer;
        }

        // Get the last open grid
        $openGrid = $gop->getGridById((string) $objParent->id);
        $currentGridId = $gop->getLastOpenedGridId();

        return $this->getGridElementHTMLMarkup($openGrid, $objElement, $currentGridId, $strBuffer, $do);
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

            // It is not necessary to edit Grid Empty items
            if (GridItemEmpty::ELEMENT_TYPE !== $objElement->type) {
                $buttons .= \sprintf('
                    <a
                    href="/contao?do=%s&id=%s&table=tl_content&act=edit&popup=1&nc=1"
                    title="%s"
                    onclick="WEM.Grid.Utils.openModalIframe({\'title\':\'%s\',\'url\':this.href,\'onHide\':function(){window.location.reload();}});return false">
                    %s
                    </a>', 
                    $do, 
                    $objElement->id,
                    StringUtil::specialchars($titleEdit), 
                    StringUtil::specialchars(str_replace("'", "\\'", $titleEdit)), 
                    Image::getHtml('edit.svg', $titleEdit)
                );
            }

            // Copy button
            $buttons .= \sprintf('
                    <a class="item-copy"
                    href="#"
                    data-element-do="%s"
                    data-element-id="%s"
                    data-element-rt="%s"
                    title="%s"
                    >
                    %s
                    </a>
                ', 
                $do, 
                $objElement->id, 
                System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(),
                StringUtil::specialchars($titleCopy), 
                Image::getHtml('copy.svg', $titleCopy)
            );


            // Delete button
            $buttons .= \sprintf('
                <a class="item-delete"
                href="#"
                data-element-id="%s"
                title="%s"
                onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()"
                >
                %s
                </a>', 
                $objElement->id, 
                StringUtil::specialchars($titleDelete), 
                $confirmDelete, 
                Image::getHtml('delete.svg', $titleDelete)
            );

            // Drag n Drop button
            $buttons .= \sprintf('
                <a
                href="#"
                onClick="return false;"
                title="%s"
                class="drag-handle">
                %s
                </a>', StringUtil::specialchars($titleDrag), Image::getHtml('drag.svg', $titleDrag));
        }

        return \sprintf('<div class="item-actions">%s (ID %s)%s%s</div>', $GLOBALS['TL_LANG']['CTE'][$objElement->type][0], $objElement->id, $withActions ? ' - ' : '', $withActions ? $buttons : '');
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

            $buttons = \sprintf('
                <a
                href="contao?do=%s&id=%s&table=tl_content&act=edit&nb=1&amp;rt=%s"
                title="%s"
                target="_blank">
                %s
                </a>', $do, $objElement->id, System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(), StringUtil::specialchars($titleEdit), Image::getHtml('edit.svg', $titleEdit));

            $buttons .= \sprintf('
                <a class="item-delete"
                href="#"
                data-element-id="%s"
                title="%s"
                onclick="if(!confirm(\'%s\'))return false;Backend.getScrollOffset()"
                >
                %s
                </a>', $objElement->id, StringUtil::specialchars($titleDelete), $confirmDelete, Image::getHtml('delete.svg', $titleDelete));

            $buttons .= \sprintf('
                <a
                href="#"
                onClick="return false;"
                title="%s"
                class="drag-handle">
                %s
                </a>', StringUtil::specialchars($titleDrag), Image::getHtml('drag.svg', $titleDrag));
        }

        return \sprintf('<div class="item-actions">%s (ID %s)%s%s</div>', $objElement->type, $objElement->id, $withActions ? ' - ' : '', $withActions ? $buttons : '');
    }

    protected function getGridElementHTMLMarkup(GridOpened $openGrid, ContentModel $objElement, string $currentGridId, string $strBuffer, string $do): string
    {
        $scopeMatcher = System::getContainer()->get('wem.scope_matcher');
        if ($scopeMatcher->isBackend()) {
            return \sprintf(
                '<div class="%s %s %s %s %s %s" data-id="%s" data-type="%s">%s%s</div>',
                implode(' ', $openGrid->getItemClassesForAllResolution()),
                GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($openGrid->getItemClassesColsForItemId((string) $objElement->id) ?: ''),
                GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($openGrid->getItemClassesRowsForItemId((string) $objElement->id) ?: ''),
                $openGrid->getItemClassesClassesForItemId((string) $objElement->id) ?: '',
                true === $openGrid->isSubGrid() ? 'be_subgrid_item' : '',
                GridItemEmpty::ELEMENT_TYPE === $objElement->type ? 'be_grid_item_empty' : '',
                $objElement->id,
                $objElement->type,
                $this->getBackendActionsForContentElement($objElement, $do, true),
                $strBuffer
            );
        }

        return \sprintf(
            '<div class="%s %s %s %s">%s</div>',
            implode(' ', $openGrid->getItemClassesForAllResolution()),
            // GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesColsForItemId($objElement->id) ?: '')),
            GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($openGrid->getItemClassesColsForItemId((string) $objElement->id) ?: ''),
            // GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($this->gridCssClassesInheritance->cleanForFrontendDisplay($openGrid->getItemClassesRowsForItemId($objElement->id) ?: '')),
            GridStart::MODE_AUTOMATIC === $openGrid->getMode() ? '' : ($openGrid->getItemClassesRowsForItemId((string) $objElement->id) ?: ''),
            $openGrid->getItemClassesClassesForItemId((string) $objElement->id) ?: '',
            $strBuffer
        );
    }
}
