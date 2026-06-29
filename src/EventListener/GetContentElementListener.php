<?php

declare(strict_types=1);

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2025 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-grid
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-grid/
 */

namespace WEM\GridBundle\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use WEM\GridBundle\Classes\GridElementsWrapper;

#[AsHook('getContentElement', priority: 100)]
class GetContentElementListener
{
    public function __construct(
        protected GridElementsWrapper $gridElementsWrapper,
    ) {
        $this->gridElementsWrapper = $gridElementsWrapper;
    }

    public function __invoke(ContentModel $contentModel, string $buffer, $element): string
    {
        return $this->gridElementsWrapper->wrapGridElements($contentModel, $buffer, Input::get('do') ?? '');
    }
}
