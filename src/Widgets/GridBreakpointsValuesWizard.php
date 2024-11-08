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

namespace WEM\GridBundle\Widgets;

use Contao\BackendUser;
use Contao\FrontendTemplate;
use Contao\Widget;

class GridBreakpointsValuesWizard extends Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Grid breakpoints.
     */
    protected array $arrGridBreakpoints = [];

    /**
     * Default constructor.
     *
     * @param array $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);
    }

    /**
     * Default Set.
     *
     * @param string $strKey
     */
    public function __set($strKey, $varValue): void
    {
        if ($strKey == 'mandatory') {
            if ($varValue) {
                $this->arrAttributes['required'] = 'required';
            } else {
                unset($this->arrAttributes['required']);
            }
        }

        parent::__set($strKey, $varValue);
    }

    /**
     * Validate the input and set the value.
     */
    public function validate(): void
    {
        parent::validate();
    }

    /**
     * Generate the widget and return it as string.
     */
    public function generate(): string
    {
        $this->import(BackendUser::class, 'User');
        $this->arrGridBreakpoints = [
            ['name' => 'all', 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointAll'], 'required' => true, 'value' => 2],
            ['name' => 'xl', 'start' => 1400, 'stop' => 0, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXl']],
            ['name' => 'lg', 'start' => 1200, 'stop' => 1399, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointLg']],
            ['name' => 'md', 'start' => 992, 'stop' => 1199, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointMd']],
            ['name' => 'sm', 'start' => 768, 'stop' => 991, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointSm']],
            ['name' => 'xs', 'start' => 620, 'stop' => 767, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXs']],
            ['name' => 'xxs', 'start' => 0, 'stop' => 619, 'label' => $GLOBALS['TL_LANG']['WEM']['GRID']['BE']['breakpointXxs']],
        ];

        if ($this->varValue) {
            foreach ($this->arrGridBreakpoints as &$b) {
                foreach ($this->varValue as $v) {
                    if ($b['name'] === $v['key']) {
                        $b['value'] = $v['value'];
                    }
                }
            }
        }

        $objTemplate = new FrontendTemplate('be_gridBreakpointsValuesWizard');
        $objTemplate->input = $this->strId;
        $objTemplate->breakpoints = $this->arrGridBreakpoints;
        $objTemplate->expertMode = $this->User->isAdmin;

        return $objTemplate->parse();
    }
}
