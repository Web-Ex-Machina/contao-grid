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

namespace WEM\GridBundle\Classes;

class GridCssClassesInheritance
{
    public function cleanForFrontendDisplay(string $cssClasses): string
    {
        $cssClasses = explode(' ', $cssClasses);

        $lastTwoChar = '';
        foreach ($cssClasses as $index => $cssClass) {
            $currentLastTwoChar = substr($cssClass, -2, 2);
            if ($currentLastTwoChar === $lastTwoChar) {
                unset($cssClasses[$index]);
            } else {
                $lastTwoChar = $currentLastTwoChar;
            }
        }

        return implode(' ', $cssClasses);
    }
}
