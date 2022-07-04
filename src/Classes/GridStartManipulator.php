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
use Contao\System;
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
    public const RESOLUTIONS = [
        self::RESOLUTION_ALL,
        self::RESOLUTION_XL,
        self::RESOLUTION_LG,
        self::RESOLUTION_MD,
        self::RESOLUTION_SM,
        self::RESOLUTION_XS,
        self::RESOLUTION_XXS,
    ];
    /* warning : This format depends on the widget used to manage the grid_cols in tl_content DCA */
    public const DEFAULT_GRID_COLS = [
        ['key' => self::RESOLUTION_ALL, 'value' => ''],
        ['key' => self::RESOLUTION_XL, 'value' => ''],
        ['key' => self::RESOLUTION_LG, 'value' => ''],
        ['key' => self::RESOLUTION_MD, 'value' => ''],
        ['key' => self::RESOLUTION_SM, 'value' => ''],
        ['key' => self::RESOLUTION_XS, 'value' => ''],
        ['key' => self::RESOLUTION_XXS, 'value' => ''],
    ];
    public const DEFAULT_GRID_ROWS = [
        ['key' => self::RESOLUTION_ALL, 'value' => ''],
        ['key' => self::RESOLUTION_XL, 'value' => ''],
        ['key' => self::RESOLUTION_LG, 'value' => ''],
        ['key' => self::RESOLUTION_MD, 'value' => ''],
        ['key' => self::RESOLUTION_SM, 'value' => ''],
        ['key' => self::RESOLUTION_XS, 'value' => ''],
        ['key' => self::RESOLUTION_XXS, 'value' => ''],
    ];
    public const DEFAULT_GRID_ITEM_COLS = [
        self::RESOLUTION_ALL => '',
        self::RESOLUTION_XL => '',
        self::RESOLUTION_LG => '',
        self::RESOLUTION_MD => '',
        self::RESOLUTION_SM => '',
        self::RESOLUTION_XS => '',
        self::RESOLUTION_XXS => '',
    ];
    public const DEFAULT_GRID_ITEM_ROWS = [
        self::RESOLUTION_ALL => '',
        self::RESOLUTION_XL => '',
        self::RESOLUTION_LG => '',
        self::RESOLUTION_MD => '',
        self::RESOLUTION_SM => '',
        self::RESOLUTION_XS => '',
        self::RESOLUTION_XXS => '',
    ];
    public const DEFAULT_GRID_CLASSES = '';
    public const DEFAULT_GRID_ITEMS = [self::PROPERTY_COLS => self::DEFAULT_GRID_ITEM_COLS, self::PROPERTY_ROWS => self::DEFAULT_GRID_ITEM_ROWS, self::PROPERTY_CLASSES => self::DEFAULT_GRID_CLASSES];
    private $gridStart;
    /** @var GridElementsCalculator */
    private $gridElementsCalculator;

    public function __construct(GridElementsCalculator $gridElementsCalculator)
    {
        $this->gridElementsCalculator = $gridElementsCalculator;
    }

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

    /**
     * Creates a new GridStartManipulator.
     *
     * @param ContentModel $gridStart The grid-start element
     *
     * @return self
     */
    public static function create(ContentModel $gridStart)
    {
        return (new self(System::getContainer()->get('wem.grid.classes.grid_elements_calculator')))
            ->setGridStart($gridStart)
        ;
    }

    /**
     * Recalculate grid items for all grids sharing the same pid & ptable.
     */
    public function recalculateElementsForAllGridSharingTheSamePidAndPtable(): self
    {
        $this->gridElementsCalculator->recalculateGridItemsByPidAndPtable((int) $this->gridStart->pid, $this->gridStart->ptable);
        $this->gridStart->refresh();

        return $this;
    }

    /**
     * Allow to set the grid cols settings.
     *
     * @param int $all The settings for all resolutions
     * @param int $xl  The settings for the XL resolution
     * @param int $lg  The settings for the LG resolution
     * @param int $md  The settings for the MD resolution
     * @param int $sm  The settings for the SM resolution
     * @param int $xs  The settings for the XS resolution
     * @param int $xxs The settings for the XXS resolution
     */
    public function setGridCols(?int $all, ?int $xl, ?int $lg, ?int $md, ?int $sm, ?int $xs, ?int $xxs): self
    {
        return $this
            ->setGridColsAll($all)
            ->setGridColsXl($xl)
            ->setGridColsLg($lg)
            ->setGridColsMd($md)
            ->setGridColsSm($sm)
            ->setGridColsXs($xs)
            ->setGridColsXxs($xxs)
        ;
    }

    /**
     * Set the grid cols value for all resolution.
     *
     * @param int $value the value
     */
    public function setGridColsAll(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_ALL, $value);

        return $this;
    }

    /**
     * Set the grid cols value for XXS resolution.
     *
     * @param int $value the value
     */
    public function setGridColsXxs(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XXS, $value);

        return $this;
    }

    /**
     * Set the grid cols value for XS resolution.
     *
     * @param int $value the value
     */
    public function setGridColsXs(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XS, $value);

        return $this;
    }

    /**
     * Set the grid cols value for SM resolution.
     *
     * @param int $value the value
     */
    public function setGridColsSm(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_SM, $value);

        return $this;
    }

    /**
     * Set the grid cols value for MD resolution.
     *
     * @param int $value the value
     */
    public function setGridColsMd(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_MD, $value);

        return $this;
    }

    /**
     * Set the grid cols value for LG resolution.
     *
     * @param int $value the value
     */
    public function setGridColsLg(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_LG, $value);

        return $this;
    }

    /**
     * Set the grid cols value for XL resolution.
     *
     * @param int $value the value
     */
    public function setGridColsXl(?int $value): self
    {
        $this->setGridColsByKeyAndValue(self::RESOLUTION_XL, $value);

        return $this;
    }

    /**
     * Get the grid cols value for all resolution.
     */
    public function getGridColsAll(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_ALL);
    }

    /**
     * Get the grid cols value for XXS resolution.
     */
    public function getGridColsXxs(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_XXS);
    }

    /**
     * Get the grid cols value for XS resolution.
     */
    public function getGridColsXs(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_XS);
    }

    /**
     * Get the grid cols value for SM resolution.
     */
    public function getGridColsSm(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_SM);
    }

    /**
     * Get the grid cols value for MD resolution.
     */
    public function getGridColsMd(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_MD);
    }

    /**
     * Get the grid cols value for LG resolution.
     */
    public function getGridColsLg(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_LG);
    }

    /**
     * Get the grid cols value for XL resolution.
     */
    public function getGridColsXl(): ?int
    {
        return $this->getGridColsByResolution(self::RESOLUTION_XL);
    }

    /**
     * Allow to set the item's settings in the grid.
     *
     * @param int    $itemId  The item's ID
     * @param array  $cols    The cols settings
     * @param array  $rows    The rows settings
     * @param string $classes The CSS classes
     */
    public function setGridItemsSettingsForItem(int $itemId, array $cols, array $rows, string $classes): self
    {
        $previousValues = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];
        $previousValues[$itemId.'_'.self::PROPERTY_COLS] = array_merge(self::DEFAULT_GRID_ITEM_COLS, $cols);
        $previousValues[$itemId.'_'.self::PROPERTY_ROWS] = array_merge(self::DEFAULT_GRID_ITEM_ROWS, $rows);
        $previousValues[$itemId.'_'.self::PROPERTY_CLASSES] = $classes;
        $this->gridStart->grid_items = serialize($previousValues);

        return $this;
    }

    /**
     * Returns the current settings for a certain item's ID.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemsSettingsForItem(int $itemId): array
    {
        $values = null !== $this->gridStart->grid_items ? unserialize($this->gridStart->grid_items) : [];

        return [
            $itemId.'_'.self::PROPERTY_COLS => \array_key_exists($itemId.'_'.self::PROPERTY_COLS, $values)
                ? $values[$itemId.'_'.self::PROPERTY_COLS]
                : self::DEFAULT_GRID_ITEMS[self::PROPERTY_COLS],
            $itemId.'_'.self::PROPERTY_ROWS => \array_key_exists($itemId.'_'.self::PROPERTY_ROWS, $values)
                ? $values[$itemId.'_'.self::PROPERTY_ROWS]
                : self::DEFAULT_GRID_ITEMS[self::PROPERTY_ROWS],
            $itemId.'_'.self::PROPERTY_CLASSES => \array_key_exists($itemId.'_'.self::PROPERTY_CLASSES, $values)
                ? $values[$itemId.'_'.self::PROPERTY_CLASSES]
                : self::DEFAULT_GRID_ITEMS[self::PROPERTY_CLASSES],
        ];
    }

    /**
     * Set the value of a property on a certain resolution for a specified item ID.
     *
     * @param int    $itemId     The item's ID
     * @param string $property   The property
     * @param string $resolution The resolution
     * @param string $value      The value
     */
    public function setGridItemsSettingsForItemAndPropertyAndResolution(int $itemId, string $property, ?string $resolution, string $value): self
    {
        $this->validateProperty($property);

        if (null !== $resolution && self::PROPERTY_CLASSES === $property) {
            $this->validateResolution($resolution);
        }

        $values = $this->getGridItemsSettingsForItem($itemId);
        if (self::PROPERTY_CLASSES === $property) {
            $values[$itemId.'_'.$property] = $value;
        } else {
            $values[$itemId.'_'.$property][$resolution] = $value;
        }

        $this->setGridItemsSettingsForItem($itemId, $values[$itemId.'_'.self::PROPERTY_COLS], $values[$itemId.'_'.self::PROPERTY_ROWS], $values[$itemId.'_'.self::PROPERTY_CLASSES]);

        return $this;
    }

    /**
     * Set classes value for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemClasses(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_CLASSES, null, $value);
    }

    /**
     * Set cols value for an item.
     *
     * @param int $itemId The item's ID
     */
    public function setGridItemCols(int $itemId, array $cols): self
    {
        $values = $this->getGridItemsSettingsForItem($itemId);
        $values[$itemId.'_'.self::PROPERTY_COLS] = $cols;

        $this->setGridItemsSettingsForItem($itemId, $values[$itemId.'_'.self::PROPERTY_COLS], $values[$itemId.'_'.self::PROPERTY_ROWS], $values[$itemId.'_'.self::PROPERTY_CLASSES]);

        return $this;
    }

    /**
     * Set rows value for an item.
     *
     * @param int $itemId The item's ID
     */
    public function setGridItemRows(int $itemId, array $rows): self
    {
        $values = $this->getGridItemsSettingsForItem($itemId);
        $values[$itemId.'_'.self::PROPERTY_ROWS] = $rows;

        $this->setGridItemsSettingsForItem($itemId, $values[$itemId.'_'.self::PROPERTY_COLS], $values[$itemId.'_'.self::PROPERTY_ROWS], $values[$itemId.'_'.self::PROPERTY_CLASSES]);

        return $this;
    }

    /**
     * Set cols value for all resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsAll(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_ALL, $value);
    }

    /**
     * Set cols value for XXS resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsXxs(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XXS, $value);
    }

    /**
     * Set cols value for XS resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsXs(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XS, $value);
    }

    /**
     * Set cols value for SM resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsSm(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_SM, $value);
    }

    /**
     * Set cols value for MD resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsMd(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_MD, $value);
    }

    /**
     * Set cols value for LG resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsLg(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_LG, $value);
    }

    /**
     * Set cols value for XL resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemColsXl(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XL, $value);
    }

    /**
     * Set rows value for all resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsAll(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_ALL, $value);
    }

    /**
     * Set rows value for XXS resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsXxs(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XXS, $value);
    }

    /**
     * Set rows value for XS resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsXs(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XS, $value);
    }

    /**
     * Set rows value for SM resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsSm(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_SM, $value);
    }

    /**
     * Set rows value for MD resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsMd(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_MD, $value);
    }

    /**
     * Set rows value for LG resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsLg(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_LG, $value);
    }

    /**
     * Set rows value for XL resolution for an item.
     *
     * @param int    $itemId The item's ID
     * @param string $value  The value
     */
    public function setGridItemRowsXl(int $itemId, string $value): self
    {
        return $this->setGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XL, $value);
    }

    /**
     * Get the value of a property on a certain resolution for a specified item ID.
     *
     * @param int    $itemId     The item's ID
     * @param string $property   The property
     * @param string $resolution The resolution
     */
    public function getGridItemsSettingsForItemAndPropertyAndResolution(int $itemId, string $property, ?string $resolution = null)
    {
        $this->validateProperty($property);
        if (null !== $resolution && self::PROPERTY_CLASSES === $property) {
            $this->validateResolution($resolution);
        }

        $values = $this->getGridItemsSettingsForItem($itemId);

        return self::PROPERTY_CLASSES === $property ? $values[$itemId.'_'.$property] : $values[$itemId.'_'.$property][$resolution];
    }

    /**
     * Get classes value for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemClasses(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_CLASSES);
    }

    /**
     * Get cols value for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemCols(int $itemId): ?array
    {
        $values = $this->getGridItemsSettingsForItem($itemId);

        return $values[$itemId.'_'.self::PROPERTY_COLS];
    }

    /**
     * Get rows value for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRows(int $itemId): ?array
    {
        $values = $this->getGridItemsSettingsForItem($itemId);

        return $values[$itemId.'_'.self::PROPERTY_ROWS];
    }

    /**
     * Get cols value for all resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsAll(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_ALL);
    }

    /**
     * Get cols value for XXS resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsXxs(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XXS);
    }

    /**
     * Get cols value for XS resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsXs(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XS);
    }

    /**
     * Get cols value for SM resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsSm(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_SM);
    }

    /**
     * Get cols value for MD resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsMd(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_MD);
    }

    /**
     * Get cols value for LG resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsLg(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_LG);
    }

    /**
     * Get cols value for XL resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemColsXl(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_COLS, self::RESOLUTION_XL);
    }

    /**
     * Get rows value for all resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsAll(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_ALL);
    }

    /**
     * Get rows value for XXS resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsXxs(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XXS);
    }

    /**
     * Get rows value for XS resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsXs(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XS);
    }

    /**
     * Get rows value for SM resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsSm(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_SM);
    }

    /**
     * Get rows value for MD resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsMd(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_MD);
    }

    /**
     * Get rows value for LG resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsLg(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_LG);
    }

    /**
     * Get rows value for XL resolution for an item.
     *
     * @param int $itemId The item's ID
     */
    public function getGridItemRowsXl(int $itemId): ?string
    {
        return $this->getGridItemsSettingsForItemAndPropertyAndResolution($itemId, self::PROPERTY_ROWS, self::RESOLUTION_XL);
    }

    /**
     * Returns a correctly formatted array of configuration for a certain item.
     *
     * @param int $itemId The item's ID
     */
    public function getDefaultItemSettingsForItem(int $itemId): array
    {
        return [
            $itemId.'_'.self::PROPERTY_COLS => self::DEFAULT_GRID_ITEMS[self::PROPERTY_COLS],
            $itemId.'_'.self::PROPERTY_ROWS => self::DEFAULT_GRID_ITEMS[self::PROPERTY_ROWS],
            $itemId.'_'.self::PROPERTY_CLASSES => self::DEFAULT_GRID_ITEMS[self::PROPERTY_CLASSES],
        ];
    }

    /**
     * Validate the property key.
     *
     * @param string $property The property key
     */
    public function validateProperty(string $property): void
    {
        if (!\in_array($property, self::PROPERTIES, true)) {
            throw new InvalidArgumentException('The property value must be one of the following : "'.implode('", "', self::PROPERTIES).'"');
        }
    }

    /**
     * Validate the resolution key.
     *
     * @param string $resolution The resolution key
     */
    public function validateResolution(string $resolution): void
    {
        if (!\in_array($resolution, self::RESOLUTIONS, true)) {
            throw new InvalidArgumentException('The resolution value must be one of the following : "'.implode('", "', self::RESOLUTIONS).'"');
        }
    }

    /**
     * Check if an item is in the grid.
     *
     * @param ContentModel $item The item
     *
     * @return bool true if found, false otherwise
     */
    public function isItemInGrid(ContentModel $item): bool
    {
        return $this->isItemIdInGrid((int) $item->id);
    }

    /**
     * Check if an item is in the grid by its id.
     *
     * @param int $id The item's id
     *
     * @return bool true if found, false otherwise
     */
    public function isItemIdInGrid(int $id): bool
    {
        return \array_key_exists($id.'_'.self::PROPERTY_CLASSES, unserialize($this->gridStart->grid_items));
    }

    /**
     * @return mixed
     */
    public function getGridElementsCalculator(): ?GridElementsCalculator
    {
        return $this->gridElementsCalculator;
    }

    /**
     * @param mixed $gridElementsCalculator
     */
    public function setGridElementsCalculator(GridElementsCalculator $gridElementsCalculator): self
    {
        $this->gridElementsCalculator = $gridElementsCalculator;

        return $this;
    }

    /**
     * Set the cols value for a certain resolution.
     *
     * @param string   $resolution The resolution
     * @param int|null $value      The value
     */
    protected function setGridColsByKeyAndValue(string $resolution, ?int $value): self
    {
        $previousValues = null !== $this->gridStart->grid_cols ? unserialize($this->gridStart->grid_cols) : self::DEFAULT_GRID_COLS;
        foreach ($previousValues as $resolutionIndex => $resolutionSettings) {
            if ($resolution === $resolutionSettings['key']) {
                $previousValues[$resolutionIndex]['value'] = $value;
            }
        }
        $this->gridStart->grid_cols = serialize($previousValues);

        return $this;
    }

    /**
     * Set the cols value for a certain resolution.
     *
     * @param string $resolution The resolution
     */
    protected function getGridColsByResolution(string $resolution): ?int
    {
        $previousValues = null !== $this->gridStart->grid_cols ? unserialize($this->gridStart->grid_cols) : self::DEFAULT_GRID_COLS;
        foreach ($previousValues as $resolutionIndex => $resolutionSettings) {
            if ($resolution === $resolutionSettings['key']) {
                return $previousValues[$resolutionIndex]['value'];
            }
        }

        return null;
    }
}
