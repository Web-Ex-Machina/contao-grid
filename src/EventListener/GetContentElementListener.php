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

namespace WEM\GridBundle\EventListener;

use Contao\ContentModel;
use Contao\Input;
use WEM\GridBundle\Classes\GridElementsWrapper;

/**
 * Grid Hooks.
 */
class GetContentElementListener
{
    /** @var GridElementsWrapper */
    protected $gridElementsWrapper;

    public function __construct(
        GridElementsWrapper $gridElementsWrapper
    ) {
        $this->gridElementsWrapper = $gridElementsWrapper;
    }

    public function __invoke(ContentModel $contentModel, string $buffer, $element): string
    {
        return $this->gridElementsWrapper->wrapGridElements($contentModel, $buffer, Input::get('do') ?? '');
    }
}
