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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\GridBundle\Elements\GridStart;

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class IncludeJsCssOnLoadCallback
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(DataContainer|null $dc = null): void
    {
        if (null === $dc || !$dc->id || 'edit' !== $this->requestStack->getCurrentRequest()->query->get('act')) {
            return;
        }

        $element = ContentModel::findById($dc->id);

        if (null === $element || GridStart::ELEMENT_TYPE !== $element->type) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/grid.css';
        $GLOBALS['TL_CSS'][] = 'bundles/wemgrid/css/backend.css';
    }
}