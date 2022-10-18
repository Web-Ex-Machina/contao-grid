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
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Exception;
use WEM\GridBundle\Classes\GridElementsCalculator;

class tlContentCallback
{
    /** @var Connection */
    protected $connection;
    /** @var GridElementsCalculator */
    private $gridElementsCalculator;

    public function __construct(
        Connection $connection,
        GridElementsCalculator $gridElementsCalculator
    ) {
        $this->connection = $connection;
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
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $dc->activeRecord->pid, $dc->activeRecord->ptable);
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

    public function onundoCallback(string $table, array $data, DataContainer $dc): void
    {
        if ('tl_content' === $table && 'grid-start' === $data['type']) {
            // restore the grid-stop
            $this->restoreClosestGridStopFromGridStart($data['id'], $dc);
        }
    }

    public function restoreClosestGridStopFromGridStart($gridStartUndoId, DataContainer $dc): void
    {
        $objGridStopUndo = null;

        $objRecordsGridStartUndo = $this->connection->prepare('SELECT * FROM tl_undo WHERE id=:id LIMIT 1')
            ->executeQuery(['id' => $dc->id])
        ;
        try {
            $objRecordsGridStartUndo = $objRecordsGridStartUndo->fetchAssociative();
        } catch (Exception $e) {
            return;
        }

        $startId = $dc->id;
        do {
            $objRecordsGridStopUndo = $this->connection->prepare('SELECT * FROM tl_undo WHERE id>:id AND tstamp >=:tstamp AND pid=:pid AND fromTable=:fromTable ORDER BY id LIMIT 1')
                ->executeQuery(['id' => $startId, 'tstamp' => $objRecordsGridStartUndo['tstamp'], 'pid' => $objRecordsGridStartUndo['pid'], 'fromTable' => $objRecordsGridStartUndo['fromTable']])
            ;
            try {
                if (0 === $objRecordsGridStopUndo->rowCount()) {
                    return;
                }
                $objGridStopUndo = $objRecordsGridStopUndo->fetchAssociative();
            } catch (Exception $e) {
                return;
            }

            if ($objGridStopUndo) {
                $data = unserialize($objGridStopUndo['data']);
                if (\array_key_exists('tl_content', $data)
                && \array_key_exists(0, $data['tl_content'])
                && \array_key_exists('type', $data['tl_content'][0])
                && 'grid-stop' === $data['tl_content'][0]['type']
                ) {
                    // everything is OK
                } else {
                    $startId = $objGridStopUndo['id'];
                    $objGridStopUndo = null;
                }
            }
        } while (null === $objGridStopUndo && 0 !== $objRecordsGridStopUndo->rowCount());
        if (!$objGridStopUndo) {
            // we did not find the grid-stop
            return;
        }

        // trick the system tomake the DC_Driver believes it is workling on the grid-stop
        $oldId = $dc->id;
        $dc2 = new DC_Table('tl_undo');
        $dc2->id = $objGridStopUndo['id'];
        try {
            $dc2->undo();
        } catch (AjaxRedirectResponseException $e) {
            // do not redirect here
        } catch (RedirectResponseException $e) {
            // do not redirect here
        }
        $dc2->id = $oldId;
    }

    public function deleteClosestGridStopFromGridStart(ContentModel $gridStart): void
    {
        $gridStop = ContentModel::findBy(['pid = ?', 'ptable = ?', 'type = ?', 'sorting > ?'], [$gridStart->pid, $gridStart->ptable, 'grid-stop', $gridStart->sorting], ['limit' => 1, 'order' => 'sorting ASC']);
        if (!$gridStop) {
            return;
        }
        // trick the system tomake the DC_Driver believes it is workling on the grid-stop
        $oldId = Input::get('id');
        $dc = new DC_Table('tl_content');
        Input::setGet('id', $gridStop->id);
        $dc->delete(true);
        // set the $_GET['id'] value to its previous value
        Input::setGet('id', $oldId);
        $gridStop->delete();
    }
}
