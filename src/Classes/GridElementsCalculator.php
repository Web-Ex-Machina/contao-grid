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
use Contao\Model\Collection;
use Contao\StringUtil;
use WEM\GridBundle\Elements\GridStart;
use WEM\GridBundle\Elements\GridStop;

class GridElementsCalculator
{
    /**
     * Recalculate grid items by pid and ptable.
     *
     * @param int       $pid          The pid
     * @param string    $ptable       The ptable
     * @param bool|int  $sortingMin   The min sorting for elements
     * @param bool|int  $sortingMax   The max sorting for elements
     * @param bool|null $isAfterACopy true if the grid was copied
     */
    public function recalculateGridItemsByPidAndPtable(int $pid, string $ptable, ?int $sortingMin = null, ?int $sortingMax = null, ?bool $isAfterACopy = false): void
    {
        $conditions = ['pid = ?', 'ptable = ?'];
        $values = [$pid, $ptable];
        if (null !== $sortingMin) {
            $conditions[] = 'sorting >= ?';
            $values[] = $sortingMin;
        }
        if (null !== $sortingMax) {
            $conditions[] = 'sorting <= ?';
            $values[] = $sortingMax;
        }
        $objItems = ContentModel::findBy($conditions, $values, ['order' => 'sorting ASC']);
        $objItemsIdsToSkip = [];
        $itemsClasses = [];
        // first we keep track of all grid_items settings
        foreach ($objItems as $objItem) {
            if (GridStart::TYPE === $objItem->type) {
                $itemsClasses += (null !== $objItem->grid_items ? StringUtil::deserialize($objItem->grid_items) : []);
            }
        }

        foreach ($objItems as $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }

            if (GridStart::TYPE === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses, $isAfterACopy));
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
            if (GridStop::TYPE === $objContents->type) {
                ++$nbGridOpened;
            } elseif (GridStart::TYPE === $objContents->type) {
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
            if (GridStart::TYPE === $objContents->type) {
                ++$nbGridOpened;
            } elseif (GridStop::TYPE === $objContents->type) {
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
     * @param ContentModel $gridStart         The "grid-start" content element
     * @param array        $objItemsIdsToSkip Array of content elements' ID to skip (not in the grid started by the current content element)
     * @param Collection   $objItems          Array of all content elements sharing the same pid & ptable with the current content element
     * @param array        $itemsClasses      Array of all item classes from all grids
     * @param bool|null    $isAfterACopy      True if is grid a copy
     *
     * @return array Array of content elements' ID to skip (for the next grid to not use the current content elements items)
     */
    protected function recalculateGridItems(ContentModel $gridStart, array $objItemsIdsToSkip, Collection $objItems, array $itemsClasses, ?bool $isAfterACopy = false): array
    {
        $gridItemsSave = null !== $gridStart->grid_items ? unserialize($gridStart->grid_items) : [];
        $gridStart->grid_items = serialize([]);
        $gsm = GridStartManipulator::create($gridStart);

        $itemIndexInGrid = 0;
        foreach ($objItems as $objItem) {
            if (\in_array($objItem->id, $objItemsIdsToSkip, true)) {
                continue;
            }

            if (GridStop::TYPE === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;

                return $objItemsIdsToSkip;
            }

            if (GridStart::TYPE === $objItem->type) {
                $objItemsIdsToSkip[] = $objItem->id;
                $objItemsIdsToSkip = array_merge($objItemsIdsToSkip, $this->recalculateGridItems($objItem, $objItemsIdsToSkip, $objItems, $itemsClasses, $isAfterACopy));
            }

            if (!$gsm->isItemInGrid($objItem)) {
                if ($isAfterACopy) {
                    // we will replace items IDS, based on the index
                    $oldKeys = array_keys($gridItemsSave);

                    $oldContentFirstKey = $oldKeys[$itemIndexInGrid * 3]; // 3 because we set 3 properties !
                    $oldContentId = substr($oldContentFirstKey, 0, strpos($oldContentFirstKey, '_'));

                    $gsm->setGridItemsSettingsForItem((int) $objItem->id,
                        $gridItemsSave[$oldContentId.'_'.GridStartManipulator::PROPERTY_COLS] ?? [],
                        $gridItemsSave[$oldContentId.'_'.GridStartManipulator::PROPERTY_ROWS] ?? [],
                        $gridItemsSave[$oldContentId.'_'.GridStartManipulator::PROPERTY_CLASSES] ?? ''
                    );

                    ++$itemIndexInGrid;
                } else {
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
