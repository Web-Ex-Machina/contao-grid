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

use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use WEM\GridBundle\Classes\GridCssClassesInheritance;
use WEM\GridBundle\Helper\GridBuilder;

/**
 * GRID for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina.
 *
 * @category ContaoBundle
 *
 * @author   Web ex Machina <contact@webexmachina.fr>
 *
 * @see     https://github.com/Web-Ex-Machina/contao-grid/
 */
class GridCssClassesInheritanceTest extends ContaoTestCase
{
    /** @var GridCssClassesInheritance */
    protected $sut;

    protected function setUp(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', realpath(__DIR__.'/../../../tests/_fake_contao'));
        $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('wem.grid.helper.grid_builder', new GridBuilder());
        \Contao\System::setContainer($container);
        $this->getTempDir();
        if (!\defined('TL_MODE')) {
            \define('TL_MODE', 'FE');
        }

        $this->sut = new GridCssClassesInheritance();
    }

    /**
     * [testCleanForFrontendDisplay description].
     *
     * @param string $cssClasses         [description]
     * @param string $expectedCssClasses [description]
     * @dataProvider dpForTestCleanForFrontendDisplay
     *
     * @return [type]                     [description]
     */
    public function testCleanForFrontendDisplay(string $cssClasses, string $expectedCssClasses)
    {
        $result = $this->sut->cleanForFrontendDisplay($cssClasses);
        $this->assertSame($expectedCssClasses, $result);
    }

    public function dpForTestCleanForFrontendDisplay(): array
    {
        return [
            'set #1' => [
                'cssClasses' => 'cols-span-2 cols-xl-2 cols-lg-3 cols-md-3 cols-sm-4 cols-xs-4 cols-xxs-4',
                'expectedCssClasses' => 'cols-span-2 cols-lg-3 cols-sm-4',
            ],
            'set #2' => [
                'cssClasses' => '',
                'expectedCssClasses' => '',
            ],
        ];
    }
}
