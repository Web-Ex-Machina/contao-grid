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
    public const PROPERTY_COLS = 'cols';
    public const PROPERTY_ROWS = 'rows';
    public const PROPERTY_CLASSES = 'classes';
    public const RESOLUTION_ALL = 'all';
    public const RESOLUTION_XXS = 'xxs';
    public const RESOLUTION_XS = 'xs';
    public const RESOLUTION_SM = 'sm';
    public const RESOLUTION_MD = 'md';
    public const RESOLUTION_LG = 'lg';
    public const RESOLUTION_XL = 'xl';
    public const PROPERTIES = [self::PROPERTY_COLS, self::PROPERTY_ROWS, self::PROPERTY_CLASSES];
    public const RESOLUTIONS = [self::RESOLUTION_ALL, self::RESOLUTION_XXS, self::RESOLUTION_XS, self::RESOLUTION_SM, self::RESOLUTION_MD, self::RESOLUTION_LG, self::RESOLUTION_XL,
    ];
    public const DEFAULT_GRID_COLS = [
        ['key' => self::RESOLUTION_ALL, 'value' => ''],
        ['key' => self::RESOLUTION_XXS, 'value' => ''],
        ['key' => self::RESOLUTION_XS, 'value' => ''],
        ['key' => self::RESOLUTION_SM, 'value' => ''],
        ['key' => self::RESOLUTION_MD, 'value' => ''],
        ['key' => self::RESOLUTION_LG, 'value' => ''],
        ['key' => self::RESOLUTION_XL, 'value' => ''],
    ];
    public const DEFAULT_GRID_ROWS = [
        ['key' => self::RESOLUTION_ALL, 'value' => ''],
        ['key' => self::RESOLUTION_XXS, 'value' => ''],
        ['key' => self::RESOLUTION_XS, 'value' => ''],
        ['key' => self::RESOLUTION_SM, 'value' => ''],
        ['key' => self::RESOLUTION_MD, 'value' => ''],
        ['key' => self::RESOLUTION_LG, 'value' => ''],
        ['key' => self::RESOLUTION_XL, 'value' => ''],
    ];
    public const DEFAULT_GRID_ITEM_COLS = [
        self::RESOLUTION_ALL => '',
        self::RESOLUTION_XXS => '',
        self::RESOLUTION_XS => '',
        self::RESOLUTION_SM => '',
        self::RESOLUTION_MD => '',
        self::RESOLUTION_LG => '',
        self::RESOLUTION_XL => '',
    ];
    public const DEFAULT_GRID_ITEM_ROWS = [
        self::RESOLUTION_ALL => '',
        self::RESOLUTION_XXS => '',
        self::RESOLUTION_XS => '',
        self::RESOLUTION_SM => '',
        self::RESOLUTION_MD => '',
        self::RESOLUTION_LG => '',
        self::RESOLUTION_XL => '',
    ];
    public const DEFAULT_GRID_CLASSES = '';
    public const DEFAULT_GRID_ITEMS = [self::PROPERTY_COLS => self::DEFAULT_GRID_ITEM_COLS, self::PROPERTY_ROWS => self::DEFAULT_GRID_ITEM_ROWS, self::PROPERTY_CLASSES => self::DEFAULT_GRID_CLASSES];
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
            self::RESOLUTION_ALL => $all,
            self::RESOLUTION_XXS => $xxs,
            self::RESOLUTION_XS => $xs,
            self::RESOLUTION_SM => $sm,
            self::RESOLUTION_MD => $md,
            self::RESOLUTION_LG => $lg,
            self::RESOLUTION_XL => $xl,
        ]);

        return $this;
    }

    public function setGridColsAll(?int $all): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_ALL, $all);

        return $this;
    }

    public function setGridColsXxs(?int $xxs): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XXS, $xxs);

        return $this;
    }

    public function setGridColsXs(?int $xs): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XS, $xs);

        return $this;
    }

    public function setGridColsSm(?int $sm): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_SM, $sm);

        return $this;
    }

    public function setGridColsMd(?int $md): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_MD, $md);

        return $this;
    }

    public function setGridColsLg(?int $lg): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_LG, $lg);

        return $this;
    }

    public function setGridColsXl(?int $xl): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XL, $xl);

        return $this;
    }

    public function setGridItemsSettingsForItem(int $itemId, array $cols, array $rows, string $classes): self
    {
        $previousValues = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];
        $previousValues[$itemId.'_'.self::PROPERTY_COLS] = $cols;
        $previousValues[$itemId.'_'.self::PROPERTY_ROWS] = $rows;
        $previousValues[$itemId.'_'.self::PROPERTY_CLASSES] = $classes;
        $this->gridStart->grid_items = serialize($previousValues);

        return $this;
    }

    public function getGridItemsSettingsForItem(int $itemId): array
    {
        $values = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];

        return [
            $itemId.'_'.self::PROPERTY_COLS => \array_key_exists($itemId.'_'.self::PROPERTY_COLS, $values) ? $values[$itemId.'_'.self::PROPERTY_COLS] : self::DEFAULT_GRID_ITEMS[self::PROPERTY_COLS],
            $itemId.'_'.self::PROPERTY_ROWS => \array_key_exists($itemId.'_'.self::PROPERTY_ROWS, $values) ? $values[$itemId.'_'.self::PROPERTY_ROWS] : self::DEFAULT_GRID_ITEMS[self::PROPERTY_ROWS],
            $itemId.'_'.self::PROPERTY_CLASSES => \array_key_exists($itemId.'_'.self::PROPERTY_CLASSES, $values) ? $values[$itemId.'_'.self::PROPERTY_CLASSES] : self::DEFAULT_GRID_ITEMS[self::PROPERTY_CLASSES],
        ];
    }

    public function setGridItemsSettingsForItemAndPropertyAndResolution(int $itemId, string $property, string $resolution, string $value): self
    {
        $this->validateProperty($property);
        $this->validateResolution($resolution);

        $values = $this->getGridItemsSettingsForItem($itemId);
        $values[$itemId.'_'.$property][$resolution] = $value;

        $this->setGridItemsSettingsForItem($itemId, $values[$itemId.'_'.self::PROPERTY_COLS], $values[$itemId.'_'.self::PROPERTY_ROWS], $values[$itemId.'_'.self::PROPERTY_CLASSES]);

        return $this;
    }

    public function getDefaultItemSettingsForItem(int $itemId): array
    {
        return [
            $itemId.'_'.self::PROPERTY_COLS => self::DEFAULT_GRID_ITEMS[self::PROPERTY_COLS],
            $itemId.'_'.self::PROPERTY_ROWS => self::DEFAULT_GRID_ITEMS[self::PROPERTY_ROWS],
            $itemId.'_'.self::PROPERTY_CLASSES => self::DEFAULT_GRID_ITEMS[self::PROPERTY_CLASSES],
        ];
    }

    protected function setGridColsByKeyAndValue(string $key, ?int $value): self
    {
        $previousValues = null !== $this->gridStart->grid_cols ? unserialize($this->gridStart->grid_cols) : self::DEFAULT_GRID_COLS;
        foreach ($previousValues as $resolutionIndex => $resolutionSettings) {
            if ($key === $resolutionSettings['key']) {
                $previousValues[$resolutionIndex]['value'] = $value;
            }
        }
        $this->gridStart->grid_cols = serialize($previousValues);

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
