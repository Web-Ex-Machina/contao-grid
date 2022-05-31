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

class GridGapValuesWizard extends \Widget
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
        $objTemplate = new \FrontendTemplate('be_gridGapValuesWizard');
        $objTemplate->input = $this->strId;
        $objTemplate->value = $this->varValue['value'] ?? '1';
        $objTemplate->unit = $this->varValue['unit'] ?? '';

        return $objTemplate->parse();
    }
}
