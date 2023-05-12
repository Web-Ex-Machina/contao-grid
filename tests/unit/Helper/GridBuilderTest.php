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
class GridBuilderTest extends ContaoTestCase
{
    /** @var GridBuilder */
    protected $sut;

    protected function setUp(): void
    {
        $this->sut = new GridBuilder();

        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', realpath(__DIR__.'/../../../tests/_fake_contao'));
        \Contao\System::setContainer($container);
        $this->getTempDir();
    }

    /**
     * @dataProvider dpForTestGetWrapperClasses
     *
     * @param callable $gridGen Callable function to generate a grid
     */
    public function testGetWrapperClasses(callable $gridGen, callable $expectedResultGen): void
    {
        $grid = $gridGen();
        $expectedResult = $expectedResultGen();

        $ret = $this->sut->getWrapperClasses($grid);

        $this->assertSame($ret, $expectedResult);
    }

    /**
     * @dataProvider dpForTestGetItemClasses
     *
     * @param callable $gridGen Callable function to generate a grid
     */
    public function testGetItemClasses(callable $gridGen, callable $expectedResultGen): void
    {
        $grid = $gridGen();
        $expectedResult = $expectedResultGen();

        $ret = $this->sut->getItemClasses($grid);

        $this->assertSame($ret, $expectedResult);
    }

    public function dpForTestGetWrapperClasses(): array
    {
        return [
            'cssgrid - custom - set #1' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_cols = serialize([
                        ['key' => 'all', 'value' => '2'],
                        ['key' => 'xxs', 'value' => ''],
                        ['key' => 'xs', 'value' => ''],
                        ['key' => 'sm', 'value' => ''],
                        ['key' => 'md', 'value' => ''],
                        ['key' => 'lg', 'value' => ''],
                        ['key' => 'xl', 'value' => ''],
                    ]);
                    $grid->grid_gap = serialize([
                        'value' => '1',
                        'unit' => 'rem',
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_CUSTOM;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'd-grid',
                        'cols-2',
                        'gap-1-rem',
                    ];
                },
            ],
            'cssgrid - custom - set #2' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_cols = serialize([
                        ['key' => 'all', 'value' => '2'],
                        ['key' => 'xxs', 'value' => '3'],
                        ['key' => 'xs', 'value' => '4'],
                        ['key' => 'sm', 'value' => '5'],
                        ['key' => 'md', 'value' => '6'],
                        ['key' => 'lg', 'value' => '7'],
                        ['key' => 'xl', 'value' => '8'],
                    ]);
                    $grid->grid_gap = serialize([
                        'value' => '3',
                        'unit' => 'em',
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_CUSTOM;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'd-grid',
                        'cols-2',
                        'cols-xxs-3',
                        'cols-xs-4',
                        'cols-sm-5',
                        'cols-md-6',
                        'cols-lg-7',
                        'cols-xl-8',
                        'gap-3-em',
                    ];
                },
            ],
            'cssgrid - automatic - set #1' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_cols = serialize([
                        ['key' => 'all', 'value' => '2'],
                        ['key' => 'xxs', 'value' => ''],
                        ['key' => 'xs', 'value' => ''],
                        ['key' => 'sm', 'value' => ''],
                        ['key' => 'md', 'value' => ''],
                        ['key' => 'lg', 'value' => ''],
                        ['key' => 'xl', 'value' => ''],
                    ]);
                    $grid->grid_gap = serialize([
                        'value' => '1',
                        'unit' => 'rem',
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'd-grid',
                        'cols-autofit',
                        'gap-1-rem',
                    ];
                },
            ],
        ];
    }

    public function dpForTestGetItemClasses(): array
    {
        return [
            'cssgrid - custom - set #1' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_items = serialize([
                        '1_classes' => 'foo',
                        '1_cols' => [
                            'all' => 'cols-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => 'cols-span-xl-3',
                        ],
                        '1_rows' => [
                            'all' => 'rows-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => '',
                        ],
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_CUSTOM;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'all' => ['item-grid'],
                        'items' => [
                            '1_classes' => 'foo',
                            '1_cols' => 'cols-span-2 cols-span-xl-3',
                            '1_rows' => 'rows-span-2',
                        ],
                    ];
                },
            ],
            'cssgrid - custom - set #2' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_items = serialize([
                        '1_classes' => 'foo',
                        '1_cols' => [
                            'all' => 'cols-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => 'cols-span-xl-3',
                        ],
                        '1_rows' => [
                            'all' => 'rows-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => '',
                        ],
                        '2_classes' => 'bar',
                        '2_cols' => [
                            'all' => '',
                            'xxs' => 'cols-span-xxs-2',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => '',
                        ],
                        '2_rows' => [
                            'all' => '',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => 'rows-span-sm-4',
                            'md' => '',
                            'lg' => '',
                            'xl' => '',
                        ],
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_CUSTOM;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'all' => ['item-grid'],
                        'items' => [
                            '1_classes' => 'foo',
                            '1_cols' => 'cols-span-2 cols-span-xl-3',
                            '1_rows' => 'rows-span-2',
                            '2_classes' => 'bar',
                            '2_cols' => 'cols-span-xxs-2',
                            '2_rows' => 'rows-span-sm-4',
                        ],
                    ];
                },
            ],
            'cssgrid - automatic - set #1' => [
                'gridGen' => function () {
                    $grid = new Contao\ContentModel();
                    $grid->grid_items = serialize([
                        '1_classes' => 'foo',
                        '1_cols' => [
                            'all' => 'cols-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => 'cols-span-xl-3',
                        ],
                        '1_rows' => [
                            'all' => 'rows-span-2',
                            'xxs' => '',
                            'xs' => '',
                            'sm' => '',
                            'md' => '',
                            'lg' => '',
                            'xl' => '',
                        ],
                    ]);
                    $grid->grid_mode = \WEM\GridBundle\Elements\GridStart::MODE_AUTOMATIC;

                    return $grid;
                },
                'expectedResultGen' => function () {
                    return [
                        'all' => ['item-grid'],
                        'items' => [
                            '1_classes' => 'foo',
                            '1_cols' => 'cols-span-2 cols-span-xl-3',
                            '1_rows' => 'rows-span-2',
                        ],
                    ];
                },
            ],
        ];
    }
}
