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

use Contao\Database\Result as DbResult;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use WEM\GridBundle\Classes\GridOpenedManager;
use WEM\GridBundle\Elements\GridStart as GridStartElement;

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
class GridOpenedManagerTest extends ContaoTestCase
{
    /** @var GridOpenedManager */
    protected $sut;

    protected function setUp(): void
    {
        $this->sut = GridOpenedManager::getInstance();

        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', realpath(__DIR__.'/../../../tests/_fake_contao'));
        $container->set('database_connection', $this->createMock(Connection::class));
        \Contao\System::setContainer($container);
        $this->getTempDir();
    }

    /**
     * [testOpenGridWillSucceed description].
     *
     * @dataProvider dpForTestOpenGridWillSucceed
     *
     * @param callable $gridGen The function to generate a grid
     */
    public function testOpenGridWillSucceed(callable $gridGen): void
    {
        $gridStart = $gridGen();

        $this->sut->openGrid($gridStart);
    }

    /**
     * [testOpenGridWillFail description].
     *
     * @dataProvider dpForTestOpenGridWillFail
     *
     * @param callable $gridGen              The function to generate a grid
     * @param callable $expectedExceptionGen The function to generate an exception
     */
    public function testOpenGridWillFail(callable $gridGen, callable $expectedExceptionGen): void
    {
        $gridStart = $gridGen();
        $expectedException = $expectedExceptionGen();

        try {
            $this->sut->openGrid($gridStart);
            $this->assertTrue('false', 'An exception should have been raised !');
        } catch (\Exception $e) {
            $this->assertSame(\get_class($e), \get_class($expectedException));
            $this->assertSame($e->getMessage(), $expectedException->getMessage());
        }
    }

    public function testGetLastOpenedGrid(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $this->sut->openGrid($gridStartFoo);
        $lastOpenedGrid = $this->sut->openGrid($gridStartBar);

        $this->assertSame($lastOpenedGrid, $this->sut->getLastOpenedGrid());
    }

    public function testGetLastOpenedGridId(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $this->sut->openGrid($gridStartFoo);
        $lastOpenedGrid = $this->sut->openGrid($gridStartBar);

        $this->assertSame($lastOpenedGrid->getid(), $this->sut->getLastOpenedGridId());
        $this->assertSame('bar', $this->sut->getLastOpenedGridId());
    }

    public function testGetPreviousLastOpenedGrid(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $previousOpenedGrid = $this->sut->openGrid($gridStartFoo);
        $this->sut->openGrid($gridStartBar);

        $this->assertSame($previousOpenedGrid, $this->sut->getPreviousLastOpenedGrid());
    }

    public function testGetPreviousLastOpenedGridId(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $previousOpenedGrid = $this->sut->openGrid($gridStartFoo);
        $this->sut->openGrid($gridStartBar);

        $this->assertSame($previousOpenedGrid->getid(), $this->sut->getPreviousLastOpenedGridId());
        $this->assertSame('foo', $this->sut->getPreviousLastOpenedGridId());
    }

    public function testGetGridByIdWillSucceed(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $gridStartFooOpened = $this->sut->openGrid($gridStartFoo);
        $gridStartBarOpened = $this->sut->openGrid($gridStartBar);

        $this->assertSame($gridStartFooOpened, $this->sut->getGridById($gridStartFooOpened->getId()));
        $this->assertSame($gridStartFooOpened, $this->sut->getGridById('foo'));

        $this->assertSame($gridStartBarOpened, $this->sut->getGridById($gridStartBarOpened->getId()));
        $this->assertSame($gridStartBarOpened, $this->sut->getGridById('bar'));
    }

    public function testGetGridByIdWillFail(): void
    {
        $gridStartFoo = new \Contao\ContentModel();
        $gridStartFoo->id = 'foo';
        $gridStartFoo->type = 'grid-start';
        $gridStartFoo->grid_preset = 'bs4';
        $gridStartFoo->grid_cols = serialize([]);

        $gridStartBar = new \Contao\ContentModel();
        $gridStartBar->id = 'bar';
        $gridStartBar->type = 'grid-start';
        $gridStartBar->grid_preset = 'bs4';
        $gridStartBar->grid_cols = serialize([]);

        $gridStartFooOpened = $this->sut->openGrid($gridStartFoo);
        $gridStartBarOpened = $this->sut->openGrid($gridStartBar);

        $this->sut->closeLastOpenedGrid();

        try {
            $this->sut->getGridById($gridStartBarOpened->getId());
            $this->assertTrue('false', 'An exception should have been raised !');
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'The grid doesn\'t exists.');
        }

        try {
            $this->sut->getGridById('bar');
            $this->assertTrue('false', 'An exception should have been raised !');
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'The grid doesn\'t exists.');
        }

        $this->sut->closeLastOpenedGrid();

        try {
            $this->sut->getGridById($gridStartFooOpened->getId());
            $this->assertTrue('false', 'An exception should have been raised !');
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'The grid doesn\'t exists.');
        }

        try {
            $this->sut->getGridById('foo');
            $this->assertTrue('false', 'An exception should have been raised !');
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'The grid doesn\'t exists.');
        }
    }

    public function dpForTestOpenGridWillFail(): array
    {
        return [
            'ContentModel with bad type' => [
                'gridGen' => function () {
                    $gridStart = new \Contao\ContentModel();
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-starttttttteeee';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
                'expectedExceptionGen' => function () {
                    return new \InvalidArgumentException('The element "Contao\ContentModel" is not a "grid-start"');
                },
            ],
            'DbResult with bad type' => [
                'gridGen' => function () {
                    $gridStart = new DbResult([], '');
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-starttttttteeee';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
                'expectedExceptionGen' => function () {
                    return new \InvalidArgumentException('The element "Contao\Database\Result" is not a "grid-start"');
                },
            ],
            'GridStartElement with bad type' => [
                'gridGen' => function () {
                    $gridStart = new GridStartElement((new \Contao\ContentModel()));
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-starttttttteeee';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
                'expectedExceptionGen' => function () {
                    return new \InvalidArgumentException('The element "WEM\GridBundle\Elements\GridStart" is not a "grid-start"');
                },
            ],
            'Grid is not a valid class' => [
                'gridGen' => function () {
                    return new \DateTime();
                },
                'expectedExceptionGen' => function () {
                    return new \InvalidArgumentException('The element "DateTime" is not a "grid-start"');
                },
            ],
        ];
    }

    public function dpForTestOpenGridWillSucceed(): array
    {
        return [
            'ContentModel' => [
                'gridGen' => function () {
                    $gridStart = new \Contao\ContentModel();
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-start';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
            ],
            'DbResult' => [
                'gridGen' => function () {
                    $gridStart = new DbResult([], '');
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-start';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
            ],
            'GridStartElement' => [
                'gridGen' => function () {
                    $gridStart = new GridStartElement((new \Contao\ContentModel()));
                    $gridStart->id = 'foo';
                    $gridStart->type = 'grid-start';
                    $gridStart->grid_preset = 'bs4';
                    $gridStart->grid_cols = serialize([]);

                    return $gridStart;
                },
            ],
        ];
    }
}
