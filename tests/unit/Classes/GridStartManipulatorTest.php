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
use WEM\GridBundle\Classes\GridStartManipulator;

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
class GridStartManipulatorTest extends ContaoTestCase //\Codeception\Test\Unit
{
    protected $gridStart;

    /** @var GridStartManipulator */
    protected $sut;

    protected function setUp(): void
    {
        $this->sut = new GridStartManipulator();

        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', realpath(__DIR__.'/../../../tests/_fake_contao'));
        \Contao\System::setContainer($container);
        $this->getTempDir();
    }

    public function testSettingAndGettingGridCols(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->sut->setGridColsAll(1);
        $this->sut->setGridColsXxs(2);
        $this->sut->setGridColsXs(3);
        $this->sut->setGridColsSm(4);
        $this->sut->setGridColsMd(5);
        $this->sut->setGridColsLg(6);
        $this->sut->setGridColsXl(7);

        $this->assertSame(1, $this->sut->getGridColsAll());
        $this->assertSame(2, $this->sut->getGridColsXxs());
        $this->assertSame(3, $this->sut->getGridColsXs());
        $this->assertSame(4, $this->sut->getGridColsSm());
        $this->assertSame(5, $this->sut->getGridColsMd());
        $this->assertSame(6, $this->sut->getGridColsLg());
        $this->assertSame(7, $this->sut->getGridColsXl());

        $this->assertNotSame(8, $this->sut->getGridColsAll());
        $this->assertNotSame(8, $this->sut->getGridColsXxs());
        $this->assertNotSame(8, $this->sut->getGridColsXs());
        $this->assertNotSame(8, $this->sut->getGridColsSm());
        $this->assertNotSame(8, $this->sut->getGridColsMd());
        $this->assertNotSame(8, $this->sut->getGridColsLg());
        $this->assertNotSame(8, $this->sut->getGridColsXl());
    }

    public function testSettingAndGettingGridItems(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);

        $this->sut->setGridItemClasses(1, 'foo_classes');
        $this->assertSame('foo_classes', $this->sut->getGridItemClasses(1));

        $this->sut->setGridItemRowsAll(1, 'col-span-1');
        $this->assertSame('col-span-1', $this->sut->getGridItemRowsAll(1));

        $this->sut->setGridItemRowsXxs(1, 'foo_rows_xxs');
        $this->assertSame('foo_rows_xxs', $this->sut->getGridItemRowsXxs(1));

        $this->sut->setGridItemRowsXs(1, 'foo_rows_xs');
        $this->assertSame('foo_rows_xs', $this->sut->getGridItemRowsXs(1));

        $this->sut->setGridItemRowsSm(1, 'foo_rows_sm');
        $this->assertSame('foo_rows_sm', $this->sut->getGridItemRowsSm(1));

        $this->sut->setGridItemRowsMd(1, 'foo_rows_md');
        $this->assertSame('foo_rows_md', $this->sut->getGridItemRowsMd(1));

        $this->sut->setGridItemRowsLg(1, 'foo_rows_lg');
        $this->assertSame('foo_rows_lg', $this->sut->getGridItemRowsLg(1));

        $this->sut->setGridItemRowsXl(1, 'foo_rows_xl');
        $this->assertSame('foo_rows_xl', $this->sut->getGridItemRowsXl(1));

        $this->sut->setGridItemColsAll(1, 'col-span-1');
        $this->assertSame('col-span-1', $this->sut->getGridItemColsAll(1));

        $this->sut->setGridItemColsXxs(1, 'foo_cols_xxs');
        $this->assertSame('foo_cols_xxs', $this->sut->getGridItemColsXxs(1));

        $this->sut->setGridItemColsXs(1, 'foo_cols_xs');
        $this->assertSame('foo_cols_xs', $this->sut->getGridItemColsXs(1));

        $this->sut->setGridItemColsSm(1, 'foo_cols_sm');
        $this->assertSame('foo_cols_sm', $this->sut->getGridItemColsSm(1));

        $this->sut->setGridItemColsMd(1, 'foo_cols_md');
        $this->assertSame('foo_cols_md', $this->sut->getGridItemColsMd(1));

        $this->sut->setGridItemColsLg(1, 'foo_cols_lg');
        $this->assertSame('foo_cols_lg', $this->sut->getGridItemColsLg(1));

        $this->sut->setGridItemColsXl(1, 'foo_cols_xl');
        $this->assertSame('foo_cols_xl', $this->sut->getGridItemColsXl(1));
    }

    public function testGetGridItemsSettingsForItemAndPropertyAndResolution(): void
    {
        // getGridItemsSettingsForItemAndPropertyAndResolution
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->sut->setGridItemClasses(1, 'foo');
        $this->sut->setGridItemRowsAll(1, 'col-span-1');
        $this->sut->setGridItemRowsXxs(1, 'foo_rows_xxs');
        $this->sut->setGridItemRowsXs(1, 'foo_rows_xs');
        $this->sut->setGridItemRowsSm(1, 'foo_rows_sm');
        $this->sut->setGridItemRowsMd(1, 'foo_rows_md');
        $this->sut->setGridItemRowsLg(1, 'foo_rows_lg');
        $this->sut->setGridItemRowsXl(1, 'foo_rows_xl');
        $this->sut->setGridItemColsAll(1, 'col-span-1');
        $this->sut->setGridItemColsXxs(1, 'foo_cols_xxs');
        $this->sut->setGridItemColsXs(1, 'foo_cols_xs');
        $this->sut->setGridItemColsSm(1, 'foo_cols_sm');
        $this->sut->setGridItemColsMd(1, 'foo_cols_md');
        $this->sut->setGridItemColsLg(1, 'foo_cols_lg');
        $this->sut->setGridItemColsXl(1, 'foo_cols_xl');

        $this->assertSame('foo', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_CLASSES, null));
        $this->assertSame('col-span-1', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_ALL));
        $this->assertSame('foo_rows_xxs', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_XXS));
        $this->assertSame('foo_rows_xs', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_XS));
        $this->assertSame('foo_rows_sm', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_SM));
        $this->assertSame('foo_rows_md', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_MD));
        $this->assertSame('foo_rows_lg', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_LG));
        $this->assertSame('foo_rows_xl', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_ROWS, GridStartManipulator::RESOLUTION_XL));

        $this->assertSame('col-span-1', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_ALL));
        $this->assertSame('foo_cols_xxs', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_XXS));
        $this->assertSame('foo_cols_xs', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_XS));
        $this->assertSame('foo_cols_sm', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_SM));
        $this->assertSame('foo_cols_md', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_MD));
        $this->assertSame('foo_cols_lg', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_LG));
        $this->assertSame('foo_cols_xl', $this->sut->getGridItemsSettingsForItemAndPropertyAndResolution(1, GridStartManipulator::PROPERTY_COLS, GridStartManipulator::RESOLUTION_XL));
    }

    public function testValidateResolutionWillFail(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->expectException(\InvalidArgumentException::class);
        $this->sut->validateResolution('wrong');
        $this->sut->validateResolution('wrong2');
    }

    public function testValidateResolutionWillSucceed(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_ALL);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_XXS);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_XS);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_SM);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_MD);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_LG);
        $this->sut->validateResolution(GridStartManipulator::RESOLUTION_XL);
    }

    public function testValidatePropertyWillFail(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->expectException(\InvalidArgumentException::class);
        $this->sut->validateProperty('wrong');
        $this->sut->validateProperty('wrong2');
    }

    public function testValidatePropertyWillSucceed(): void
    {
        $this->resestGridStart();
        $this->sut->setGridStart($this->gridStart);
        $this->sut->validateProperty(GridStartManipulator::PROPERTY_COLS);
        $this->sut->validateProperty(GridStartManipulator::PROPERTY_ROWS);
        $this->sut->validateProperty(GridStartManipulator::PROPERTY_CLASSES);
    }

    protected function resestGridStart(): self
    {
        $this->gridStart = new \Contao\ContentModel();
        $this->gridStart->type = 'grid-start';
        $this->gridStart->pid = 1;
        $this->gridStart->ptable = 'tl_article';
        $this->gridStart->grid_cols = serialize(GridStartManipulator::DEFAULT_GRID_COLS);

        return $this;
    }
}
