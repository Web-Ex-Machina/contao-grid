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

namespace WEM\GridBundle\Widgets;

class GridBreakpointsValuesWizard extends \Widget
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
     * Grid preset.
     *
     * @var string
     */
    protected $strGridPreset = '';

    /**
     * Grid breakpoints.
     *
     * @var array
     */
    protected $arrGridBreakpoints = [];

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
        switch ($strKey) {
            case 'mandatory':
                if ($varValue) {
                    $this->arrAttributes['required'] = 'required';
                } else {
                    unset($this->arrAttributes['required']);
                }

                parent::__set($strKey, $varValue);
                break;

            default:
                parent::__set($strKey, $varValue);
        }
    }

    /**
     * Validate the input and set the value.
     */
    public function validate(): void
    {
        $mandatory = $this->mandatory;
        $varValue = $this->getPost($this->strName);
        parent::validate();
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $this->strGridPreset = $this->activeRecord->grid_preset;
        $this->arrGridBreakpoints = [
            ['name' => 'all', 'label' => 'Général', 'required' => true, 'value' => 2],
            ['name' => 'xxs', 'start' => 0, 'stop' => 619, 'label' => 'XXS'],
            ['name' => 'xs', 'start' => 620, 'stop' => 767, 'label' => 'XS'],
            ['name' => 'sm', 'start' => 768, 'stop' => 991, 'label' => 'SM'],
            ['name' => 'md', 'start' => 992, 'stop' => 1199, 'label' => 'MD'],
            ['name' => 'lg', 'start' => 1200, 'stop' => 1399, 'label' => 'LG'],
            ['name' => 'xl', 'start' => 1400, 'stop' => 0, 'label' => 'XL'],
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

        $objTemplate = new \FrontendTemplate('be_gridBreakpointsValuesWizard');
        $objTemplate->input = $this->strId;
        $objTemplate->preset = $this->strGridPreset;
        $objTemplate->breakpoints = $this->arrGridBreakpoints;

        return $objTemplate->parse();
    }
}
