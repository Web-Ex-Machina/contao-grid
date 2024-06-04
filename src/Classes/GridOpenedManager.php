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

namespace WEM\GridBundle\Classes;

use Contao\ContentModel;
use Contao\Database\Result as DbResult;
use Contao\System;
use Exception;
use InvalidArgumentException;
use WEM\GridBundle\Elements\GridStart as GridStartElement;
use WEM\GridBundle\Helper\GridBuilder;

class GridOpenedManager
{
    protected int $level = 0;

    private static $instance;

    private GridBuilder $gridBuilder;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = (new self())->setGridBuilder(System::getContainer()->get('wem.grid.helper.grid_builder'));
        }

        return self::$instance;
    }

    /**
     * Open a new grid.
     *
     * @param ContentModel|DbResult|GridStartElement $element The grid-start element
     */
    public function openGrid($element): GridOpened
    {
        $this->validateElementAsAGridStart($element);

        if (\array_key_exists('WEM', $GLOBALS)
        && \array_key_exists('GRID', $GLOBALS['WEM'])
        && \array_key_exists((string) $element->id, $GLOBALS['WEM']['GRID'])
        ) {
            return $GLOBALS['WEM']['GRID'][(string) $element->id];
        }

        $grid = new GridOpened();
        $grid
            ->setId((string) $element->id)
            ->setCols(
                null === $element->grid_cols
                ? []
                : (!\is_array($element->grid_cols) ? unserialize($element->grid_cols) : $element->grid_cols)
            )
            ->setWrapperClasses($this->gridBuilder->getWrapperClasses($element))
            ->setItemClasses($this->gridBuilder->getItemClasses($element))
            ->setItemClassesForm($this->gridBuilder->getItemClasses($element, true))
            ->setLevel($this->level)
            ->setMode($element->grid_mode)
        ;
        if ('BE' === TL_MODE) {
            $grid->addItemClassesForAllResolution('be_item_grid helper');
        }

        if (!empty($element->cssID[1])) {
            $grid->addWrapperClasses($element->cssID[1]);
        }

        $GLOBALS['WEM']['GRID'][(string) $element->id] = $grid;

        ++$this->level;

        return $grid;
    }

    /**
     * Closes the last opened grid.
     */
    public function closeLastOpenedGrid(): void
    {
        --$this->level;
        array_pop($GLOBALS['WEM']['GRID']);
    }

    /**
     * Returns the last opened grid.
     *
     * @return GridOpened|null the last openend grid if it exists, null otherwise
     */
    public function getLastOpenedGrid(): ?GridOpened
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || 0 === \count($GLOBALS['WEM']['GRID'])
        ) {
            return null;
        }

        $grid = end($GLOBALS['WEM']['GRID']);

        reset($GLOBALS['WEM']['GRID']);

        return $grid;
    }

    /**
     * Returns the last opened grid id.
     *
     * @return string|null the last openend grid id if it exists, null otherwise
     */
    public function getLastOpenedGridId(): ?string
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || 0 === \count($GLOBALS['WEM']['GRID'])
        ) {
            return null;
        }


        $key = array_key_last($GLOBALS['WEM']['GRID']);

        reset($GLOBALS['WEM']['GRID']);

        return (string) $key;
    }

    /**
     * Returns the previous to last opened grid.
     *
     * @return GridOpened|null the previous to last openend grid if it exists, null otherwise
     */
    public function getPreviousLastOpenedGrid(): ?GridOpened
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || 1 >= \count($GLOBALS['WEM']['GRID'])
        ) {
            return null;
        }

        $gridsCopy = $GLOBALS['WEM']['GRID'];
        array_pop($gridsCopy);

        $grid = end($gridsCopy);

        unset($gridsCopy);

        return $grid;
    }

    /**
     * Returns the previous to last opened grid id.
     *
     * @return string|null the previous to last openend grid id if it exists, null otherwise
     */
    public function getPreviousLastOpenedGridId(): ?string
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || 1 >= \count($GLOBALS['WEM']['GRID'])
        ) {
            return null;
        }

        $gridsCopy = $GLOBALS['WEM']['GRID'];
        array_pop($gridsCopy);

        $key = array_key_last($gridsCopy);

        unset($gridsCopy);

        return (string) $key;
    }

    /**
     * Find a grid by its ID.
     *
     * @param string $id The id
     *
     * @throws Exception if no grid is found
     *
     * @return GridOpened the grid
     */
    public function getGridById(string $id): GridOpened
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || !\array_key_exists($id, $GLOBALS['WEM']['GRID'])
        ) {
            throw new Exception("The grid doesn't exists.");
        }

        return $GLOBALS['WEM']['GRID'][$id];
    }

    /**
     * Return the parent grid of an element.
     *
     * @param ContentModel $element [description]
     *
     * @return GridOpened|null The grid if found, null otherwise
     */
    public function getParentGrid(ContentModel $element): ?GridOpened
    {
        $arrGrid = null;
        foreach ($GLOBALS['WEM']['GRID'] as $g) {
            if ($g->hasChildByItemId((string) $element->id)) {
                $arrGrid = $g;
                break;
            }
        }

        return $arrGrid;
    }

    /**
     * Check if an element is a grid-start.
     *
     * @param ContentModel|DbResult $element The element to check
     *
     * @throws InvalidArgumentException if the element is not a grid start
     */
    public function validateElementAsAGridStart($element): void
    {
        if (!(is_a($element, DbResult::class) || $element instanceof ContentModel::class || $element instanceof GridStartElement::class)
            || 'grid-start' !== $element->type
        ) {
            throw new InvalidArgumentException('The element "'.\get_class($element).'" is not a "grid-start"');
        }
    }

    public function getGridBuilder(): ?GridBuilder
    {
        return $this->gridBuilder;
    }

    public function setGridBuilder(GridBuilder $gridBuilder): self
    {
        $this->gridBuilder = $gridBuilder;

        return $this;
    }
}
