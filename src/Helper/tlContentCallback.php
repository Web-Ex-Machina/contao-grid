<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\Helper;

use Contao\ContentModel;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Exception;
use WEM\GridBundle\Classes\GridElementsCalculator;
use WEM\GridBundle\Elements\GridStart;

class tlContentCallback
{
    protected Connection $connection;

    private GridElementsCalculator $gridElementsCalculator;

    public function __construct(
        Connection $connection,
        GridElementsCalculator $gridElementsCalculator
    ) {
        $this->connection = $connection;
        $this->gridElementsCalculator = $gridElementsCalculator;
    }

    public function includeJSCSS(): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        $this->createMissingGridStartStop($dc);
        $objItem = ContentModel::findOneById($dc->activeRecord->id);
        $objItem->refresh();
        // otherwise the $objItem still has its previous "sorting" value ...
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $dc->activeRecord->pid, $dc->activeRecord->ptable);
    }

    public function oncutCallback(DataContainer $dc): void
    {
        $objItem = ContentModel::findOneById($dc->id);
        $objItem->refresh();
        // otherwise the $objItem still has its previous "sorting" value ...
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

        $sessionKey = 'WEMGRID_ondeleteCallback';
        $session = System::getContainer()->get('session');
        if ($session->has($sessionKey)) {
            return;
        }

        $session->set($sessionKey, 1);

        if ('grid-start' === $objItem->type) {
            $this->deleteCorrespondingGridStopFromGridStart($objItem);
        } elseif ('grid-stop' === $objItem->type) {
            $this->deleteCorrespondingGridStartFromGridStop($objItem);
        }

        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
        $session->remove($sessionKey);
    }

    public function onundoCallback(string $table, array $data, DataContainer $dc): void
    {
        $sessionKey = 'WEMGRID_onundoCallback';
        $session = System::getContainer()->get('session');
        if ($session->has($sessionKey)) {
            return;
        }

        $session->set($sessionKey, 1);
        if (ContentModel::getTable() === $table && 'grid-start' === $data['type']) {
            // restore the grid-stop
            $this->restoreClosestGridStopFromGridStart($data, $dc);
        } elseif (ContentModel::getTable() === $table && 'grid-stop' === $data['type']) {
            // restore the grid-start
            $this->restoreClosestGridStartFromGridStop($data, $dc);
        }

        $session->remove($sessionKey);
    }

    /**
     * Restores the corresponding "grid-stop" content element to the "grid-start" `tl_undo`.`data` (unserialized) in parameter.
     *
     * @param DataContainer $dc The DataContainer
     * @throws \Doctrine\DBAL\Exception
     */
    public function restoreClosestGridStopFromGridStart(array $gridStartUndoData, DataContainer $dc): void
    {
        $arrRecordGridStartUndo = $this->getUndoElementAsArray($dc->id);
        if (!\is_array($arrRecordGridStartUndo)) {
            return;
        }

        $results = $this->getDeletedElementsOnSameEntity($arrRecordGridStartUndo);
        $arrData = $this->buildUseableArrayOfDataForDeletedElementsOnSameEntity($results);

        $gridStopUndoId = null;
        $nbGridOpened = 0;

        foreach ($arrData as $sorting => $row) {
            if (null !== $gridStopUndoId) {
                break;
            }

            // only work on elements placed AFTER the grid-start
            if ((int) $sorting > (int) $gridStartUndoData['sorting']) {
                if ('grid-start' === $row['data'][ContentModel::getTable()][0]['type']) {
                    ++$nbGridOpened;
                } elseif ('grid-stop' === $row['data'][ContentModel::getTable()][0]['type']) {
                    if (0 === $nbGridOpened) {
                        //it's the one
                        $gridStopUndoId = $row['undo_id'];
                    } else {
                        --$nbGridOpened;
                    }
                }
            }
        }

        if ($gridStopUndoId) {
            $dc2 = new DC_Table('tl_undo');
            $dc2->id = $gridStopUndoId;
            try {
                $dc2->undo();
            } catch (AjaxRedirectResponseException|RedirectResponseException $e) {
                // do not redirect here
            }
        }
    }

    /**
     * Restores the corresponding "grid-start" content element to the "grid-stop" `tl_undo`.`data` (unserialized) in parameter.
     *
     * @param array $gridStopUndoData The `tl_undo`.`data` value (unserialized)
     * @param DataContainer $dc The DataContainer
     * @throws \Doctrine\DBAL\Exception
     */
    public function restoreClosestGridStartFromGridStop(array $gridStopUndoData, DataContainer $dc): void
    {
        $arrRecordGridStopUndo = $this->getUndoElementAsArray($dc->id);
        if (!\is_array($arrRecordGridStopUndo)) {
            return;
        }

        $results = $this->getDeletedElementsOnSameEntity($arrRecordGridStopUndo);
        $arrData = $this->buildUseableArrayOfDataForDeletedElementsOnSameEntity($results);

        $gridStartUndoId = null;
        $nbGridOpened = 0;
        $arrData = array_reverse($arrData, true);
        foreach ($arrData as $sorting => $row) {
            if (null !== $gridStartUndoId) {
                break;
            }

            // only work on elements placed BEFORE the grid-stop
            if ((int) $sorting < (int) $gridStopUndoData['sorting']) {
                if ('grid-stop' === $row['data'][ContentModel::getTable()][0]['type']) {
                    ++$nbGridOpened;
                } elseif ('grid-start' === $row['data'][ContentModel::getTable()][0]['type']) {
                    if (0 === $nbGridOpened) {
                        //it's the one
                        $gridStartUndoId = $row['undo_id'];
                    } else {
                        --$nbGridOpened;
                    }
                }
            }
        }

        if ($gridStartUndoId) {
            $dc2 = new DC_Table('tl_undo');
            $dc2->id = $gridStartUndoId;
            try {
                $dc2->undo();
            } catch (AjaxRedirectResponseException|RedirectResponseException $e) {
                // do not redirect here
            }
        }
    }

    /**
     * Delete the "grid-stop" content element corresponding to the "grid-start" element in parameter.
     *
     * @param ContentModel $gridStart The "grid-start" content element
     */
    public function deleteCorrespondingGridStopFromGridStart(ContentModel $gridStart): void
    {
        $gridStop = $this->gridElementsCalculator->getGridStopCorrespondingToGridStart($gridStart);
        if (!$gridStop) {
            return;
        }

        $dc = new DC_Table(ContentModel::getTable());
        $dc->id = $gridStop->id;
        $dc->delete(true);
        $gridStop->delete();
    }

    /**
     * Delete the "grid-start" content element corresponding to the "grid-stop" element in parameter.
     *
     * @param ContentModel $gridStop The "grid-stop" content element
     */
    public function deleteCorrespondingGridStartFromGridStop(ContentModel $gridStop): void
    {
        $gridStart = $this->gridElementsCalculator->getGridStartCorrespondingToGridStop($gridStop);
        if (!$gridStart) {
            return;
        }

        $dc = new DC_Table(ContentModel::getTable());
        $dc->id = $gridStart->id;
        $dc->delete(true);
        $gridStart->delete();
    }

    protected function createMissingGridStartStop(DataContainer $dc): void
    {
        if (null !== $dc->activeRecord) {
            if ('grid-start' === $dc->activeRecord->type) {
                $this->createMissingGridStop($dc);
            } elseif ('grid-stop' === $dc->activeRecord->type) {
                $this->createMissingGridStart($dc);
            }
        }
    }

    /**
     * Returns the list of tl_undo items corresponding to the same element as the one in parameter (onlmy those with close deletion dates).
     *
     * @param array $gridStartStopUndoElementData The `tl_undo`.`data` columns value
     *
     * @return Result|null The list if items found, null otherwise
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getDeletedElementsOnSameEntity(array $gridStartStopUndoElementData): ?Result
    {
        return $this->connection->prepare('SELECT * FROM tl_undo WHERE tstamp BETWEEN :tstamp1 AND :tstamp2 AND pid=:pid AND fromTable=:fromTable ORDER BY id ASC')
            ->executeQuery(['tstamp1' => (int) $gridStartStopUndoElementData['tstamp'] - 5, 'tstamp2' => (int) $gridStartStopUndoElementData['tstamp'] + 5, 'pid' => $gridStartStopUndoElementData['pid'], 'fromTable' => $gridStartStopUndoElementData['fromTable']])
        ;
    }

    /**
     * Build an "usable" array from tl_undo items.
     *
     * @param Result $results The results set
     *
     * @return array An array on the form [sorting=>['data'=>tl_undo.data unserialized,'undo_id'=>tl_undo.id],...]
     * @throws \Doctrine\DBAL\Exception
     */
    protected function buildUseableArrayOfDataForDeletedElementsOnSameEntity(Result $results): array
    {
        $arrDataFormatted = [];

        if (0 === $results->rowCount()) {
            return $arrDataFormatted;
        }

        $arrData = $results->fetchAllAssociative();
        foreach ($arrData as $row) {
            $rowData = unserialize($row['data']);
            if (\array_key_exists(ContentModel::getTable(), $rowData)
            && \array_key_exists(0, $rowData[ContentModel::getTable()])
            && \array_key_exists('type', $rowData[ContentModel::getTable()][0])
            && \array_key_exists('sorting', $rowData[ContentModel::getTable()][0])
            && (
                'grid-start' === $rowData[ContentModel::getTable()][0]['type']
                || 'grid-stop' === $rowData[ContentModel::getTable()][0]['type']
                )
            ) {
                $arrDataFormatted[$rowData[ContentModel::getTable()][0]['sorting']] = [
                    'data' => $rowData,
                    'undo_id' => $row['id'],
                ];
            }
        }

        return $arrDataFormatted;
    }

    /**
     * Creates a GridStop element if one is missing.
     *
     * @param DataContainer $dc The DataContainer
     */
    protected function createMissingGridStop(DataContainer $dc): void
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
                $objElement->sorting = $dc->activeRecord->sorting + 1;
                $objElement->save();
            }
        }
    }

    /**
     * Creates a GridStart element if one is missing.
     *
     * @param DataContainer $dc The DataContainer
     */
    protected function createMissingGridStart(DataContainer $dc): void
    {
        if (null !== $dc->activeRecord && 'grid-stop' === $dc->activeRecord->type) {
            $gridStarts = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-start']);
            $gridStops = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, 'grid-stop']);

            if ($gridStarts < $gridStops) {
                $objElement = new ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = 'grid-start';
                $objElement->grid_mode = GridStart::MODE_AUTOMATIC;
                $objElement->sorting = $dc->activeRecord->sorting - 1;
                $objElement->save();
            }
        }
    }

    /**
     * Returns a `tl_undo` as an associative array.
     *
     * @param int|string $id The record's id
     *
     * @return array|null The record as an associative array if foudn, null otherwise
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getUndoElementAsArray($id): ?array
    {

        $objRecordsUndo = $this->connection
            ->prepare('SELECT * FROM tl_undo WHERE id=:id LIMIT 1')
            ->executeQuery(['id' => $id]);

        try {
            $objRecordUndo = $objRecordsUndo->fetchAssociative();
        } catch (Exception $exception) {
            return null;
        }

        return $objRecordUndo;
    }
}
