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

namespace WEM\GridBundle\Helper;

use Contao\ContentModel;
use Contao\DataContainer;
use WEM\GridBundle\Classes\GridElementsCalculator;

class tlContentCallback
{
    /** @var GridElementsCalculator */
    private $gridElementsCalculator;

    public function __construct(GridElementsCalculator $gridElementsCalculator)
    {
        $this->gridElementsCalculator = $gridElementsCalculator;
    }

    /**
     * Automaticly create a GridStop element when creating a GridStart element.
     */
    public function createGridStop(DataContainer $dc): void
    {
        if (null !== $dc->activeRecord && 'grid-start' === $dc->activeRecord->type) {
            $gridStarts = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-start']);
            $gridStops = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-stop']);

            if ($gridStarts > $gridStops) {
                $objElement = new ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = 'grid-stop';
                // $objElement->sorting = $dc->activeRecord->sorting + 64;
                $objElement->sorting = $dc->activeRecord->sorting + 1;
                $objElement->save();
            }
        }
    }

    public function includeJSCSS(): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        $this->createGridStop($dc);
        $objItem = ContentModel::findOneById($dc->activeRecord->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        // When submitting a grid with subgrids, all styles are saved in parent grid instead of each subgrids
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $dc->activeRecord->pid, $dc->activeRecord->ptable, true);
    }

    public function oncutCallback(DataContainer $dc): void
    {
        $objItem = ContentModel::findOneById($dc->id);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function oncopyCallback(int $itemId, DataContainer $dc): void
    {
        $objItem = ContentModel::findOneById($itemId);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        // ugly fix to allow duplication of element in grid edition
        $objItem->tstamp = 0 !== (int) $objItem->tstamp ? $objItem->tstamp : time();
        $objItem->save();
        // end of ugly fix
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function ondeleteCallback(DataContainer $dc, int $undoItemId): void
    {
        if (!$dc->id) {
            return;
        }
        $objItem = ContentModel::findOneById($dc->id);
        if (!$objItem) {
            return;
        }
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        if ('grid-start' === $objItem->type) {
            $this->deleteClosestGridStopFromGridStart($objItem);
        }
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function deleteClosestGridStopFromGridStart(ContentModel $gridStart): void
    {
        $gridStop = ContentModel::findBy(['pid = ?', 'ptable = ?', 'type = ?', 'sorting > ?'], [$gridStart->pid, $gridStart->ptable, 'grid-stop', $gridStart->sorting], ['limit' => 1, 'order' => 'sorting ASC']);
        if ($gridStop) {
            $gridStop->delete();
        }
    }
}
