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

class GridOpened
{
    /** @var string */
    protected $id;

    /** @var array */
    protected $cols;

    /** @var array */
    protected $wrapper_classes;

    /** @var array */
    protected $item_classes;

    /** @var array */
    protected $item_classes_form;

    /** @var int */
    protected $level;

    /** @var string */
    protected $mode;

    public function isSubGrid(): bool
    {
        return 1 <= $this->level;
    }

    public function addItemClassesForAllResolution(string $classes): self
    {
        $this->item_classes['all'][] = $classes;

        return $this;
    }

    public function getItemClassesForAllResolution(): array
    {
        return $this->item_classes['all'];
    }

    public function getItemClassesColsForItemId(string $itemId): ?string
    {
        return $this->item_classes['items'][$itemId.'_cols'];
    }

    public function getItemClassesRowsForItemId(string $itemId): ?string
    {
        return $this->item_classes['items'][$itemId.'_rows'];
    }

    public function getItemClassesFormColsForItemIdAndResolution(string $itemId, string $resolution): ?string
    {
        return $this->item_classes_form['items'][$itemId.'_cols'][$resolution];
    }

    public function getItemClassesFormRowsForItemIdAndResolution(string $itemId, string $resolution): ?string
    {
        return $this->item_classes_form['items'][$itemId.'_rows'][$resolution];
    }

    public function getItemClassesClassesForItemId(string $itemId): ?string
    {
        return $this->item_classes['items'][$itemId.'_classes'];
    }

    public function getItemClassesFormClassesForItemId(string $itemId): ?string
    {
        return $this->item_classes_form['items'][$itemId.'_classes'];
    }

    public function hasChildByItemId(string $itemId): bool
    {
        return \array_key_exists($itemId.'_classes', $this->item_classes['items']);
    }

    public function addWrapperClasses(string $classes): self
    {
        $this->wrapper_classes[] = $classes;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCols()
    {
        return $this->cols;
    }

    public function setCols(array $cols): self
    {
        $this->cols = $cols;

        return $this;
    }

    public function getWrapperClasses(): ?array
    {
        return $this->wrapper_classes;
    }

    public function getWrapperClassesWithoutResolutionSpecificClasses(): ?array
    {
        $arrClasses = [];
        foreach ($this->wrapper_classes as $class) {
            if (!preg_match('/^cols-(.*)-(\d{1,2})$/', $class)
                && !preg_match('/^rows-(.*)-(\d{1,2})$/', $class)
            ) {
                $arrClasses[] = $class;
            }
        }

        return $arrClasses;
    }

    public function getWrapperColsClassesWithoutResolutionSpecificClasses(): ?array
    {
        $arrClasses = $this->getWrapperClassesWithoutResolutionSpecificClasses();
        foreach ($arrClasses as $index => $class) {
            if (!preg_match('/^cols-(\d{1,2})$/', $class)) {
                unset($arrClasses[$index]);
            }
        }

        return $arrClasses;
    }

    public function setWrapperClasses(array $wrapper_classes): self
    {
        $this->wrapper_classes = $wrapper_classes;

        return $this;
    }

    public function getItemClasses(): ?array
    {
        return $this->item_classes;
    }

    public function setItemClasses(array $item_classes): self
    {
        $this->item_classes = $item_classes;

        return $this;
    }

    public function getItemClassesForm(): ?array
    {
        return $this->item_classes_form;
    }

    public function setItemClassesForm(array $item_classes_form): self
    {
        $this->item_classes_form = $item_classes_form;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }
}
