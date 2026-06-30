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
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\GridBundle\Classes\GridCssClassesInheritance;
use WEM\GridBundle\Classes\GridOpenedManager;

#[AsContentElement(
    type: GridStart::ELEMENT_TYPE,
    category: 'miscellaneous',
    nestedFragments: true,
)]
class GridStart extends AbstractContentElementController
{
    public const ELEMENT_TYPE = 'grid-start';
    public const MODE_CUSTOM = 'custom';
    public const MODE_AUTOMATIC = 'automatic';

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Generate the content element.
     */
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $elements = [];

        foreach ($template->get('nested_fragments') as $i => $reference) {
            $nestedModel = $reference->getContentModel();

            if (!$nestedModel instanceof ContentModel) {
                $nestedModel = $this->framework->getAdapter(ContentModel::class)->findById($nestedModel);
            }

            $header = StringUtil::deserialize($nestedModel->sectionHeadline, true);

            $elements[] = [
                'header' => $header['value'] ?? '',
                'header_tag' => $header['unit'] ?? 'h2',
                'reference' => $reference,
            ];
        }

        $template->set('elements', $elements);

        return $template->getResponse();
    }
}
