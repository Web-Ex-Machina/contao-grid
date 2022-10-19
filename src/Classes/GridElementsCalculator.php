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

class GridElementsCalculator
{
    /**
     * Recalculate grid items by pid and ptable.
     *
     * @param int    $pid    The pid
     * @param string $ptable The ptable
     */
    public function recalculateGridItemsByPidAndPtable(int $pid, string $ptable): void
    {
        $objItems = ContentModel::findBy(['pid = ?', 'ptable = ?'], [$pid, $ptable], ['order' => 'sorting ASC']);
        $objItemsIdsToSkip = [];
        $itemsClasses = [];
        // first we keep track of all grid_items settings
        foreach ($objItems as $index => $objItem) {
            if ('grid-start' === $objItem->type) {
                $itemsClasses = $itemsClasses + (null !== $objItem->grid_items ? deserialize($objItem->grid_items) : []);
            }
        }
        foreach ($objItems as $index => $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }
            if ('grid-start' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses));
            }
        }
    }

    /**
     * Returns the grid-start element corresponding to the grid-stop in paramter, if it exists.
     *
     * @param ContentModel $gridStop The "grid-stop" content element
     *
     * @return ContentModel|null The ContentModel if found, null otherwise
     */
    public function getGridStartCorrespondingToGridStop(ContentModel $gridStop): ?ContentModel
    {
        $objContents = ContentModel::findBy(['pid=?', 'ptable=?', 'sorting<?'], [$gridStop->pid, $gridStop->ptable, $gridStop->sorting], ['order' => 'sorting DESC']);
        if (!$objContents) {
            return null;
        }
        $nbGridOpened = 0;
        while ($objContents->next()) {
            if ('grid-stop' === $objContents->type) {
                ++$nbGridOpened;
            } elseif ('grid-start' === $objContents->type) {
                if (0 === $nbGridOpened) {
                    return $objContents->current();
                }
                --$nbGridOpened;
            }
        }

        return null;
    }

    /**
     * Returns the grid-stop element corresponding to the grid-start in paramter, if it exists.
     *
     * @param ContentModel $gridSart The "grid-start" content element
     *
     * @return ContentModel|null The ContentModel if found, null otherwise
     */
    public function getGridStopCorrespondingToGridStart(ContentModel $gridSart): ?ContentModel
    {
        $objContents = ContentModel::findBy(['pid=?', 'ptable=?', 'sorting>?'], [$gridSart->pid, $gridSart->ptable, $gridSart->sorting], ['order' => 'sorting ASC']);
        if (!$objContents) {
            return null;
        }
        $nbGridOpened = 0;
        while ($objContents->next()) {
            if ('grid-start' === $objContents->type) {
                ++$nbGridOpened;
            } elseif ('grid-stop' === $objContents->type) {
                if (0 === $nbGridOpened) {
                    return $objContents->current();
                }
                --$nbGridOpened;
            }
        }

        return null;
    }

    /**
     * Recalculate elements inside a grid.
     *
     * @param ContentModel             $gridStart         The "grid-start" content element
     * @param array                    $objItemsIdsToSkip Array of content elements' ID to skip (not in the grid started by the current content element)
     * @param \Contao\Model\Collection $objItems          Array of all content elements sharing the same pid & ptable with the current content element
     *
     * @return array Array of content elements' ID to skip (for the next grid to not use the current content elements items)
     */
    protected function recalculateGridItems(ContentModel $gridStart, array $objItemsIdsToSkip, \Contao\Model\Collection $objItems, array $itemsClasses): array
    {
        $gridItemsSave = null !== $gridStart->grid_items ? unserialize($gridStart->grid_items) : [];
        $gridStart->grid_items = serialize([]);
        $gsm = GridStartManipulator::create($gridStart);

        foreach ($objItems as $index => $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }
            if ('grid-stop' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;

                return $objItemsIdsToSkip;
            }
            if ('grid-start' === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses));
            }

            if (!$gsm->isItemInGrid($objItem)) {
                $gsm->setGridItemsSettingsForItem((int) $objItem->id,
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_COLS] ?? [],
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS] ?? [],
                        $gridItemsSave[$objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES] ?? ''
                    );
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_COLS, $itemsClasses)) {
                    $gsm->setGridItemCols((int) $objItem->id, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_COLS]);
                }
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS, $itemsClasses)) {
                    $gsm->setGridItemRows((int) $objItem->id, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_ROWS]);
                }
                if (\array_key_exists($objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES, $itemsClasses)) {
                    $gsm->setGridItemsSettingsForItemAndPropertyAndResolution((int) $objItem->id, GridStartManipulator::PROPERTY_CLASSES, null, $itemsClasses[$objItem->id.'_'.GridStartManipulator::PROPERTY_CLASSES]);
                }
                $gridStart = $gsm->getGridStart();
                $gridStart->save();
                $gsm->setGridStart($gridStart);
            }
            $objItemsIdsToSkip[] = $objItem->id;
        }

        return $objItemsIdsToSkip;
    }
}
