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
use Contao\Database\Result as DbResult;
use Exception;
use WEM\GridBundle\Helper\GridBuilder;

class GridOpenedManager
{
    /** @var int */
    protected $level = 0;
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function openGrid($element, ?bool $isSubGrid = false): void
    {
        $this->validateElement($element);

        $grid = [
            'preset' => $element->grid_preset,
            'cols' => !\is_array($element->grid_cols) ? deserialize($element->grid_cols) : $element->grid_cols,
            'wrapper_classes' => GridBuilder::getWrapperClasses($element),
            'item_classes' => GridBuilder::getItemClasses($element),
            'item_classes_form' => GridBuilder::getItemClasses($element, true),
            'level' => $this->level,
            'id' => (string) $element->id,
        ];

        if ('' !== $element->cssID[1]) {
            $grid['wrapper_classes'][] = $element->cssID[1];
        }

        $grid['item_classes']['all'][] = 'be_item_grid helper';
        $grid['subgrid'] = $isSubGrid;

        $GLOBALS['WEM']['GRID'][(string) $element->id] = $grid;

        ++$this->level;
    }

    public function closeLastOpenedGrid(): void
    {
        --$this->level;
        array_pop($GLOBALS['WEM']['GRID']);
    }

    public function getLastOpenedGrid(): array
    {
        $grid = end($GLOBALS['WEM']['GRID']);

        reset($GLOBALS['WEM']['GRID']);

        return $grid;
    }

    public function getLastOpenedGridId(): string
    {
        end($GLOBALS['WEM']['GRID']);

        $key = key($GLOBALS['WEM']['GRID']);

        reset($GLOBALS['WEM']['GRID']);

        return (string) $key;
    }

    public function getGridById(string $id)
    {
        if (!\array_key_exists('WEM', $GLOBALS)
            || !\array_key_exists('GRID', $GLOBALS['WEM'])
            || !\array_key_exists($id, $GLOBALS['WEM']['GRID'])
        ) {
            throw new Exception('The grid doesn\'t exists.');
        }

        return $GLOBALS['WEM']['GRID'][$id];
    }

    public function fillGridChildren(ContentModel $element): ?string
    {
        $currentGridId = null;
        foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
            if ($k !== $element->id) {
                if (!\array_key_exists('elements', $GLOBALS['WEM']['GRID'][$k])) {
                    $GLOBALS['WEM']['GRID'][$k]['elements'] = [];
                }
                $GLOBALS['WEM']['GRID'][$k]['elements'][] = $element->id;
                $currentGridId = $k;
            }
        }

        return (string) $currentGridId;
    }

    public function getParentGrid(ContentModel $element): ?array
    {
        $arrGrid = null;
        foreach ($GLOBALS['WEM']['GRID'] as $k => $g) {
            if (\is_array($g['item_classes']['items']) && \array_key_exists($element->id.'_classes', $g['item_classes']['items'])) {
                $arrGrid = $g;
                break;
            }
        }

        return $arrGrid;
    }

    public function validateElement($element): void
    {
        if (!(is_a($element, DbResult::class) || is_a($element, ContentModel::class))
            || 'grid-start' !== $element->type
        ) {
            throw new Exception('The element is not a "grid-start"');
        }
    }
}
