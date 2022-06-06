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
use InvalidArgumentException;

class GridStartManipulator
{
    public const DEFAULT_GRID_COLS = ['all' => '', 'xxs' => '', 'xs' => '', 'sm' => '', 'md' => '', 'lg' => '', 'xl' => ''];
    public const DEFAULT_GRID_ROWS = ['all' => '', 'xxs' => '', 'xs' => '', 'sm' => '', 'md' => '', 'lg' => '', 'xl' => ''];
    public const DEFAULT_GRID_CLASSES = '';
    public const DEFAULT_GRID_ITEMS = ['cols' => self::DEFAULT_GRID_COLS, 'rows' => self::DEFAULT_GRID_ROWS, 'classes' => self::DEFAULT_GRID_CLASSES];
    public const PROPERTIES = ['cols', 'rows', 'classes'];
    public const RESOLUTIONS = ['all', 'xxs', 'xs', 'sm', 'md', 'lg', 'xl'];
    private $gridStart;

    public function getGridStart(): ContentModel
    {
        return $this->gridStart;
    }

    public function setGridStart(ContentModel $gridStart): self
    {
        if ('grid-start' !== $gridStart->type) {
            throw new InvalidArgumentException('The argument is not a grid-start content element !');
        }
        $this->gridStart = $gridStart;

        return $this;
    }

    public static function create(ContentModel $gridStart)
    {
        return (new self())->setGridStart($gridStart);
    }

    public function recalculateElements(): self
    {
        \WEM\GridBundle\Helper\GridBuilder::recalculateGridItemsByPidAndPtable((int) $this->gridStart->pid, $this->gridStart->ptable);
        $this->gridStart->refresh();

        return $this;
    }

    public function setGridCols(?int $all, ?int $xxs, ?int $xs, ?int $sm, ?int $md, ?int $lg, ?int $xl): self
    {
        $this->gridStart->grid_cols = serialize([
            'all' => $all,
            'xxs' => $xxs,
            'xs' => $xs,
            'sm' => $sm,
            'md' => $md,
            'lg' => $lg,
            'xl' => $xl,
        ]);

        return $this;
    }

    public function setGridColsAll(?int $all): self
    {
        $this->setGridColsByKeyAndValue('all', $all);

        return $this;
    }

    public function setGridColsXxs(?int $xxs): self
    {
        $this->setGridColsByKeyAndValue('xxs', $xxs);

        return $this;
    }

    public function setGridColsXs(?int $xs): self
    {
        $this->setGridColsByKeyAndValue('xs', $xs);

        return $this;
    }

    public function setGridColsSm(?int $sm): self
    {
        $this->setGridColsByKeyAndValue('sm', $sm);

        return $this;
    }

    public function setGridColsMd(?int $md): self
    {
        $this->setGridColsByKeyAndValue('md', $md);

        return $this;
    }

    public function setGridColsLg(?int $lg): self
    {
        $this->setGridColsByKeyAndValue('lg', $lg);

        return $this;
    }

    public function setGridColsXl(?int $xl): self
    {
        $this->setGridColsByKeyAndValue('xl', $xl);

        return $this;
    }

    public function setGridItemsSettingsForItem(int $itemId, string $cols, string $rows, string $classes): self
    {
        $previousValues = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];
        $previousValues[$itemId.'_cols'] = $cols;
        $previousValues[$itemId.'_rows'] = $rows;
        $previousValues[$itemId.'_classes'] = $classes;
        $this->gridStart->grid_items = serialize($previousValues);

        return $this;
    }

    public function getGridItemsSettingsForItem(int $itemId): array
    {
        $values = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];

        return [
            $itemId.'_cols' => \array_key_exists($itemId.'_cols', $values) ? $values[$itemId.'_cols'] : self::DEFAULT_GRID_ITEMS['cols'],
            $itemId.'_rows' => \array_key_exists($itemId.'_rows', $values) ? $values[$itemId.'_rows'] : self::DEFAULT_GRID_ITEMS['rows'],
            $itemId.'_classes' => \array_key_exists($itemId.'_classes', $values) ? $values[$itemId.'_classes'] : self::DEFAULT_GRID_ITEMS['classes'],
        ];
    }

    public function setGridItemsSettingsForItemAndPropertyAndResolution(int $itemId, string $property, string $resolution, string $value): self
    {
        $this->validateProperty($property);
        $this->validateResolution($resolution);

        $values = $this->getGridItemsSettingsForItem($itemId);
        $values[$itemId.'_'.$property][$resolution] = $value;
        $this->setGridItemsSettingsForItem($itemId, $values[$itemId.'_cols'], $values[$itemId.'_rows'], $values[$itemId.'_classes']);

        return $this;
    }

    protected function setGridColsByKeyAndValue(string $key, ?int $value): self
    {
        $previousValues = null !== $this->gridStart->grid_cols ? unserialize($this->gridStart->grid_cols) : self::DEFAULT_GRID_COLS;
        $this->gridStart->grid_cols = serialize(array_merge($previousValues, [$key => $value]));

        return $this;
    }

    protected function validateProperty(string $property): void
    {
        if (!\in_array($property, self::PROPERTIES, true)) {
            throw new InvalidArgumentException('The property value must be one of the following : '.implode('', self::PROPERTIES));
        }
    }

    protected function validateResolution(string $resolution): void
    {
        if (!\in_array($resolution, self::RESOLUTIONS, true)) {
            throw new InvalidArgumentException('The resolution value must be one of the following : '.implode('', self::RESOLUTIONS));
        }
    }
}
