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
use Exception;

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

    public function openGrid(ContentModel $element, ?bool $isSubGrid = false): void
    {
        $this->validateElement($element);
        ++$this->level;

        $GLOBALS['WEM']['GRID'][(string) $element->id] = [
            'preset' => $element->grid_preset,
            'cols' => !\is_array($element->grid_cols) ? deserialize($element->grid_cols) : $element->grid_cols,
            'wrapper_classes' => GridBuilder::getWrapperClasses($element),
            'item_classes' => GridBuilder::getItemClasses($element),
            'level' => 0,
            'id' => (string) $element->id,
        ];

        if ('' !== $element->cssID[1]) {
            $GLOBALS['WEM']['GRID'][$element->id]['wrapper_classes'][] = $element->cssID[1];
        }

        $GLOBALS['WEM']['GRID'][$element->id]['item_classes']['all'][] = 'be_item_grid helper';

        if ($isSubGrid) {
            $GLOBALS['WEM']['GRID'][$element->id]['subgrid'] = true;
            $GLOBALS['WEM']['GRID'][$element->id]['level'] = $this->level;
        }
    }

    public function closeLastOpenedGrid(): void
    {
        --$this->level;
        array_pop($GLOBALS['WEM']['GRID']);
    }

    public function validateElement(ContentModel $element): void
    {
        if ('grid-start' !== $element->type) {
            throw new Exception('The element is not a "grid-start"');
        }
    }
}
