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

namespace WEM\GridBundle\Helper;

use Contao\ContentModel;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use WEM\GridBundle\Classes\GridElementsCalculator;
use WEM\GridBundle\Classes\GridStartManipulator;
use WEM\GridBundle\Elements\GridStart;
use WEM\GridBundle\Elements\GridStop;

class tlContentCallback
{
    protected Connection $connection;

    private GridElementsCalculator $gridElementsCalculator;

    public function __construct(
        Connection $connection,
        GridElementsCalculator $gridElementsCalculator,
    ) {
        $this->connection = $connection;
        $this->gridElementsCalculator = $gridElementsCalculator;
    }

    public function includeJSCSS(): void
    {
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';

        $session = System::getContainer()->get('request_stack')->getSession();
        if (
            \is_array($session->get('CLIPBOARD'))
            && 1 === \count($session->get('CLIPBOARD'))
            && \array_key_exists('tl_content', $session->get('CLIPBOARD'))
            && 0 === \count($session->get('CLIPBOARD')['tl_content'])
        ) {
            $sessionBe = $session->getBag('contao_backend');
            $sessionBe->remove('WEMGRID_oncopyCallback_index');
            $sessionBe->remove('WEMGRID_oncopyCallback_ids');
        }
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
        $session = System::getContainer()->get('request_stack')->getSession();

        $objItem = ContentModel::findOneById($dc->id);
        $objItem->refresh();
        // otherwise the $objItem still has its previous "sorting" value ...
        $objItem->tstamp = 0 !== (int) $objItem->tstamp ? $objItem->tstamp : time();
        $objItem->save();

        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
    }

    public function oncopyCallback(int $itemId, DataContainer $dc): void
    {
        // copy 1 tl_article
        // GET act=copy&do=article
        // CLIPBOARD => tl_content && tl_article
        // copy 1 tl_content
        // GET act=copy&id=XXX => copy item XXX
        // copy 2 tl_content
        // GET act=copyAll => copy multiple items

        $blnJustForceGridItemsRecalculation = false;

        $session = System::getContainer()->get('request_stack')->getSession();
        $sessionBe = $session->getBag('contao_backend');

        if (!$sessionBe->has('WEMGRID_oncopyCallback_index')) {
            $sessionBe->set('WEMGRID_oncopyCallback_index', 0);
        } else {
            $sessionBe->set('WEMGRID_oncopyCallback_index', ((int) $sessionBe->get('WEMGRID_oncopyCallback_index')) + 1);
        }
        if (!$sessionBe->has('WEMGRID_oncopyCallback_ids')) {
            $sessionBe->set('WEMGRID_oncopyCallback_ids', []);
        }

        $sessionBe->set('WEMGRID_oncopyCallback_ids', array_merge($sessionBe->get('WEMGRID_oncopyCallback_ids'), [$itemId]));

        if (
            \is_array($session->get('CLIPBOARD'))
            && 1 === \count($session->get('CLIPBOARD'))
            && \array_key_exists('tl_content', $session->get('CLIPBOARD'))
        ) {
            // We are copying tl_content ONLY
            if ('copy' === \Contao\Input::get('act')) {
                // only 1 item copied
                $blnJustForceGridItemsRecalculation = true;
            }

            if ('copyAll' === \Contao\Input::get('act')) {
                // multiple items copied
                $idsToCopy = $session->get('CURRENT')['IDS'];

                $nbGridStart = ContentModel::countBy(['id IN ('.implode(',', array_map('\intval', $idsToCopy)).') AND type = ?'], [GridStart::ELEMENT_TYPE]);
                $nbGridStop = ContentModel::countBy(['id IN ('.implode(',', array_map('\intval', $idsToCopy)).') AND type = ?'], [GridStop::ELEMENT_TYPE]);
                if ($nbGridStart !== $nbGridStop
                    // || (0 === $nbGridStart && 0 === $nbGridStop)
                ) {
                    // not the same number of grid start & stop
                    // do not take any chance, just recalculate everything
                    $blnJustForceGridItemsRecalculation = true;
                }
            }
        }

        if ($blnJustForceGridItemsRecalculation) {
            $objItem = ContentModel::findOneById($itemId);
            $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
            // ugly fix to allow duplication of element in grid edition
            $objItem->tstamp = 0 !== (int) $objItem->tstamp ? $objItem->tstamp : time();
            $objItem->save();
            $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
            $this->copyGridElementConfigurationFromOneGridToAnother((int) $itemId, (int) $dc->id);

            $sessionBe->remove('WEMGRID_oncopyCallback_index');
            $sessionBe->remove('WEMGRID_oncopyCallback_ids');

            return;
        }

        // we are copying article or page or whatever
        $objItem = ContentModel::findOneById($itemId);
        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...
        // ugly fix to allow duplication of element in grid edition
        $objItem->tstamp = 0 !== (int) $objItem->tstamp ? $objItem->tstamp : time();
        $objItem->save();
        // end of ugly fix

        if (GridStart::ELEMENT_TYPE === $objItem->type) {
            $sessionKey = 'WEMGRID_oncopyCallback_'.$objItem->id;
            if ($sessionBe->has($sessionKey)) {
                return;
            }

            $sessionBe->set($sessionKey, $dc->id); // new grid-start ID reference old grid-start ID
        } elseif (GridStop::ELEMENT_TYPE === $objItem->type) {
            $objNewGridStart = $this->gridElementsCalculator->getGridStartCorrespondingToGridStop($objItem);
            if (null === $objNewGridStart) {
                return;
            }

            $sessionKey = 'WEMGRID_oncopyCallback_'.$objNewGridStart->id;
            if (!$sessionBe->has($sessionKey)) {
                return;
            }

            $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable, (int) $objNewGridStart->sorting, (int) $objItem->sorting, true);
            $sessionBe->remove($sessionKey);
        }

        if ($session->has('CURRENT')
            // && count($session->get('CURRENT')['IDS']) === (int) $sessionBe->get('WEMGRID_oncopyCallback_index')
            && \count($session->get('CURRENT')['IDS']) === \count($sessionBe->get('WEMGRID_oncopyCallback_ids'))
        ) {
            $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);
            // for each copied items
            // get original id (index of WEMGRID_oncopyCallback_ids === index of CURRENT[IDS])
            // retrieve gridstart referencing its original id
            // if found, find gridstart referencing its new id
            // if found, merge data

            foreach ($sessionBe->get('WEMGRID_oncopyCallback_ids') as $index => $newId) {
                $this->copyGridElementConfigurationFromOneGridToAnother((int) $newId, (int) $session->get('CURRENT')['IDS'][$index]);
            }

            $sessionBe->remove('WEMGRID_oncopyCallback_index');
            $sessionBe->remove('WEMGRID_oncopyCallback_ids');
        }
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

        $session = System::getContainer()->get('request_stack')->getSession();
        $sessionBe = $session->getBag('contao_backend');

        if (!$sessionBe->has('WEMGRID_ondeleteCallback_index')) {
            $sessionBe->set('WEMGRID_ondeleteCallback_index', 0);
        } else {
            $sessionBe->set('WEMGRID_ondeleteCallback_index', ((int) $sessionBe->get('WEMGRID_ondeleteCallback_index')) + 1);
        }
        if (!$sessionBe->has('WEMGRID_ondeleteCallback_ids')) {
            $sessionBe->set('WEMGRID_ondeleteCallback_ids', []);
        }

        $sessionBe->set('WEMGRID_ondeleteCallback_ids', array_merge($sessionBe->get('WEMGRID_ondeleteCallback_ids'), [$objItem->id]));

        $objItem->refresh(); // otherwise the $objItem still has its previous "sorting" value ...

        $sessionKey = 'WEMGRID_ondeleteCallback';
        $session = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
        if ($session->has($sessionKey)) {
            return;
        }

        $session->set($sessionKey, 1);

        if (GridStart::ELEMENT_TYPE === $objItem->type) {
            $this->deleteCorrespondingGridStopFromGridStart($objItem);
        } elseif (GridStop::ELEMENT_TYPE === $objItem->type) {
            $this->deleteCorrespondingGridStartFromGridStop($objItem);
        }

        $session->remove($sessionKey);

        if ($session->has('CURRENT')
            && \count($session->get('CURRENT')['IDS']) === \count($sessionBe->get('WEMGRID_ondeleteCallback_ids'))
        ) {
            $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $objItem->pid, $objItem->ptable);

            $sessionBe->remove('WEMGRID_ondeleteCallback_index');
            $sessionBe->remove('WEMGRID_ondeleteCallback_ids');
        }
    }

    public function onundoCallback(string $table, array $data, DataContainer $dc): void
    {
        $sessionKey = 'WEMGRID_onundoCallback';
        $session = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
        if ($session->has($sessionKey)) {
            return;
        }

        $session->set($sessionKey, 1);
        if (ContentModel::getTable() === $table && GridStart::ELEMENT_TYPE === $data['type']) {
            // restore the grid-stop
            $this->restoreClosestGridStopFromGridStart($data, $dc);
        } elseif (ContentModel::getTable() === $table && GridStop::ELEMENT_TYPE === $data['type']) {
            // restore the grid-start
            $this->restoreClosestGridStartFromGridStop($data, $dc);
        }

        $session->remove($sessionKey);
    }

    /**
     * Restores the corresponding "grid-stop" content element to the "grid-start" `tl_undo`.`data` (unserialized) in parameter.
     *
     * @param DataContainer $dc The DataContainer
     *
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
                if (GridStart::ELEMENT_TYPE === $row['data'][ContentModel::getTable()][0]['type']) {
                    ++$nbGridOpened;
                } elseif (GridStop::ELEMENT_TYPE === $row['data'][ContentModel::getTable()][0]['type']) {
                    if (0 === $nbGridOpened) {
                        // it's the one
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
     * @param array         $gridStopUndoData The `tl_undo`.`data` value (unserialized)
     * @param DataContainer $dc               The DataContainer
     *
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
                if (GridStop::ELEMENT_TYPE === $row['data'][ContentModel::getTable()][0]['type']) {
                    ++$nbGridOpened;
                } elseif (GridStart::ELEMENT_TYPE === $row['data'][ContentModel::getTable()][0]['type']) {
                    if (0 === $nbGridOpened) {
                        // it's the one
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
            if (GridStart::ELEMENT_TYPE === $dc->activeRecord->type) {
                $this->createMissingGridStop($dc);
            } elseif (GridStop::ELEMENT_TYPE === $dc->activeRecord->type) {
                $this->createMissingGridStart($dc);
            }
        }
    }

    /**
     * Returns the list of tl_undo items corresponding to the same element as the one in parameter (onlmy those with close deletion dates).
     *
     * @param array $gridStartStopUndoElementData The `tl_undo`.`data` columns value
     *
     * @throws \Doctrine\DBAL\Exception
     *
     * @return Result|null The list if items found, null otherwise
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
     * @throws \Doctrine\DBAL\Exception
     *
     * @return array An array on the form [sorting=>['data'=>tl_undo.data unserialized,'undo_id'=>tl_undo.id],...]
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
                GridStart::ELEMENT_TYPE === $rowData[ContentModel::getTable()][0]['type']
                || GridStop::ELEMENT_TYPE === $rowData[ContentModel::getTable()][0]['type']
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
        if (null !== $dc->activeRecord && GridStart::ELEMENT_TYPE === $dc->activeRecord->type) {
            $gridStarts = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, GridStart::ELEMENT_TYPE]);
            $gridStops = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, GridStop::ELEMENT_TYPE]);

            if ($gridStarts > $gridStops) {
                $objElement = new ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = GridStop::ELEMENT_TYPE;
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
        if (null !== $dc->activeRecord && GridStop::ELEMENT_TYPE === $dc->activeRecord->type) {
            $gridStarts = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, GridStart::ELEMENT_TYPE]);
            $gridStops = ContentModel::countBy(['pid = ?', 'ptable = ?', 'type = ?'], [$dc->activeRecord->pid, $dc->activeRecord->ptable, GridStop::ELEMENT_TYPE]);

            if ($gridStarts < $gridStops) {
                $objElement = new ContentModel();
                $objElement->tstamp = time();
                $objElement->pid = $dc->activeRecord->pid;
                $objElement->ptable = $dc->activeRecord->ptable;
                $objElement->type = GridStart::ELEMENT_TYPE;
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
     * @throws \Doctrine\DBAL\Exception
     *
     * @return array|null The record as an associative array if foudn, null otherwise
     */
    protected function getUndoElementAsArray($id): ?array
    {
        $objRecordsUndo = $this->connection
            ->prepare('SELECT * FROM tl_undo WHERE id=:id LIMIT 1')
            ->executeQuery(['id' => $id])
        ;

        try {
            $objRecordUndo = $objRecordsUndo->fetchAssociative();
        } catch (\Exception $exception) {
            return null;
        }

        return $objRecordUndo;
    }

    protected function copyGridElementConfigurationFromOneGridToAnother(int $newId, int $oldId): void
    {
        $db = Database::getInstance();
        $objGridStartOldData = $db->prepare('SELECT id FROM tl_content WHERE type=? AND grid_items LIKE ?')->execute(GridStart::ELEMENT_TYPE, '%'.$oldId.'_cols%')->fetchAssoc();
        $objGridStartNewData = $db->prepare('SELECT id FROM tl_content WHERE type=? AND grid_items LIKE ?')->execute(GridStart::ELEMENT_TYPE, '%'.$newId.'_cols%')->fetchAssoc();

        if (false === $objGridStartOldData || false === $objGridStartNewData) {
            return;
        }

        $objGridStartOld = ContentModel::findByPk($objGridStartOldData['id']);
        $objGridStartNew = ContentModel::findByPk($objGridStartNewData['id']);

        $objGSMOld = GridStartManipulator::create($objGridStartOld);
        $objGSMNew = GridStartManipulator::create($objGridStartNew);

        $oldItemData = $objGSMOld->getGridItemsSettingsForItem($oldId);

        $arrayKeys = array_keys($oldItemData);

        $objGSMNew->setGridItemsSettingsForItem($newId, $oldItemData[$arrayKeys[0]] ?? [], $oldItemData[$arrayKeys[1]] ?? [], $oldItemData[$arrayKeys[2]] ?? '');

        $objGridStartNew = $objGSMNew->getGridStart();

        $objGridStartNew->save();
    }
}
