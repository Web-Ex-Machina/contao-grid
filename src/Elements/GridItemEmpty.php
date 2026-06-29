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

namespace WEM\GridBundle\Elements;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\GridBundle\Classes\GridOpenedManager;

#[AsContentElement(
    type: GridItemEmpty::ELEMENT_TYPE,
    category: 'texts',
    template: 'ce_grid_item_empty', 
    nestedFragments: false,
)]
class GridItemEmpty extends AbstractContentElementController
{
    public const ELEMENT_TYPE = 'grid-item-empty';

    /**
     * Generate the content element.
     */
    protected function getResponse(
        FragmentTemplate $template, 
        ContentModel $model, 
        Request $request
    ): Response 
    {
        // Get the last open grid
        if (\is_array($GLOBALS['WEM']['GRID'])) {
            $gop = GridOpenedManager::getInstance();
            // Send the grid_id to template
            $template->grid_id = $gop->getLastOpenedGridId();
        }

        return $template->getResponse();
    }
}
