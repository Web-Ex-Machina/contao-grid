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

namespace WEM\GridBundle\Elements;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\Database;
use Contao\System;
use WEM\GridBundle\Classes\GridOpenedManager;

/**
 * Content Element "grid-start".
 */
class GridStart extends ContentElement
{
    public const MODE_CUSTOM = 'custom';

    public const MODE_AUTOMATIC = 'automatic';

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_grid_start';

    /**
     * Generate the content element.
     */
    protected function compile(): void
    {
        // Backend template
        if (TL_MODE === 'BE' && !$this->isForGridElementWizard) {
            $this->strTemplate = 'be_wildcard';
            $this->Template = new BackendTemplate($this->strTemplate);
            $this->Template->title = $GLOBALS['TL_LANG']['CTE'][$this->type][1];

            if (self::MODE_CUSTOM === $this->grid_mode) {
                $this->arrGridBreakpoints = [
                    ['name' => 'all', 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointAll'], 'required' => true],
                    ['name' => 'xl', 'start' => 1400, 'stop' => 0, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXl']],
                    ['name' => 'lg', 'start' => 1200, 'stop' => 1399, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointLg']],
                    ['name' => 'md', 'start' => 992, 'stop' => 1199, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointMd']],
                    ['name' => 'sm', 'start' => 768, 'stop' => 991, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointSm']],
                    ['name' => 'xs', 'start' => 620, 'stop' => 767, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXs']],
                    ['name' => 'xxs', 'start' => 0, 'stop' => 619, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXxs']],
                ]; /** @todo - make it generic per grid */
                $breakpoints = [];
                // $arrGridValues = GridBuilder::getWrapperClasses($this);
                $arrGridValues = System::getContainer()->get('wem.grid.helper.grid_builder')->getWrapperClasses($this);
                foreach ($arrGridValues as $b) {
                    $b = explode('-', $b);
                    if ('cols' !== $b[0]) {
                        continue;
                    }

                    if (2 === \count($b)) {
                        $breakpoint = $this->getBreakpointData('all');
                        $val = $b[1];
                    } elseif (3 === \count($b)) {
                        $breakpoint = $this->getBreakpointData($b[1]);
                        $val = $b[2];
                    }

                    if (0 !== (int) $val) {
                        $breakpoints[] = $breakpoint['label'].': '.sprintf($GLOBALS['TL_LANG']['WEM']['GRID']['BE']['nbColsOptionLabel'], $val);
                    }
                }

                $this->Template->wildcard = 'Config: '.implode(', ', $breakpoints);
            } else {
                $this->Template->wildcard = 'Config: '.$GLOBALS['TL_LANG']['tl_content']['grid_mode']['automatic'];
            }
        }

        // Check if the very next element is a grid-stop element
        $objNextElement = Database::getInstance()->prepare('SELECT * FROM tl_content WHERE pid = ? AND ptable = ? AND sorting > ? AND invisible = "" ORDER BY sorting ASC')->limit(1)->execute([$this->pid, $this->ptable, $this->sorting]);

        // Update : I need it opened otherwise empty nested grid is buggy in BE
        if (1 > $objNextElement->numRows) {
            $this->Template->doNotPrint = true;
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

        $gridCssClassesInheritance = new \WEM\GridBundle\Classes\GridCssClassesInheritance();
        $this->objModel->classes = explode(' ', $gridCssClassesInheritance->cleanForFrontendDisplay(implode(' ', $arrGrid->getWrapperClasses())));

        // Send the grid_id to template
        $this->Template->grid_id = $this->id;
    }

    protected function getBreakpointData($name)
    {
        foreach ($this->arrGridBreakpoints as $b) {
            if ($name === $b['name']) {
                return $b;
            }
        }
    }
}
