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
