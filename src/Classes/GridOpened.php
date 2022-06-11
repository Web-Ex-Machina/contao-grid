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

class GridOpened
{
    /** @var string */
    protected $id;
    /** @var string */
    protected $preset;
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
    /** @var array */
    protected $elements;

    public function __construct()
    {
    }

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

    public function addWrapperClasses(string $classes): self
    {
        $this->wrapper_classes[] = $classes;

        return $this;
    }

    public function addElement(string $elementId)
    {
        $this->elements[] = $elementId;

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

    public function getPreset(): ?string
    {
        return $this->preset;
    }

    public function setPreset(string $preset): self
    {
        $this->preset = $preset;

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

    public function getElements(): ?array
    {
        return $this->elements;
    }

    public function setElements(array $elements): self
    {
        $this->elements = $elements;

        return $this;
    }
}
