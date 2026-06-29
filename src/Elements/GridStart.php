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
use Contao\Database;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\GridBundle\Classes\GridCssClassesInheritance;
use WEM\GridBundle\Classes\GridOpenedManager;

#[AsContentElement(
    type: GridStart::ELEMENT_TYPE,
    category: 'texts',
    template: 'ce_grid_start', 
    nestedFragments: true,
)]
class GridStart extends AbstractContentElementController
{
    public const ELEMENT_TYPE = 'grid-start';
    public const MODE_CUSTOM = 'custom';
    public const MODE_AUTOMATIC = 'automatic';

    /**
     * Generate the content element.
     */
    protected function getResponse(
        FragmentTemplate $template, 
        ContentModel $model, 
        Request $request
    ): Response 
    {
        // Check if the very next element is a grid-stop element
        $objNextElement = Database::getInstance()->prepare('SELECT * FROM tl_content WHERE pid = ? AND ptable = ? AND sorting > ? AND invisible = "" ORDER BY sorting ASC')->limit(1)->execute($this->pid, $this->ptable, $this->sorting);

        // Update : I need it opened otherwise empty nested grid is buggy in BE
        if (1 > $objNextElement->numRows) {
            $template->doNotPrint = true;
        }

        $gop = GridOpenedManager::getInstance();
        try {
            $arrGrid = $gop->getGridById((string) $this->id);
        } catch (\Exception $exception) {
            $gop->openGrid($this);
            $arrGrid = $gop->getGridById((string) $this->id);
        }

        // Add the classes to the Model so the main class can use it correct
        if (\is_array($this->objModel->classes)) {
            $this->objModel->classes = array_merge($arrGrid->getWrapperClasses(), $this->objModel->classes);
        } else {
            $this->objModel->classes = $arrGrid->getWrapperClasses();
        }

        $gridCssClassesInheritance = new GridCssClassesInheritance();
        $this->objModel->classes = explode(' ', $gridCssClassesInheritance->cleanForFrontendDisplay(implode(' ', $arrGrid->getWrapperClasses())));

        // Send the grid_id to template
        $template->grid_id = $this->id;

        return $template->getResponse();
    }
}
