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

use Contao\Input;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Symfony\Contracts\Translation\TranslatorInterface;
use WEM\GridBundle\Classes\GridStartManipulator;
use WEM\GridBundle\Controller\GridBuilderController;

class GridBuilderControllerTest extends ContaoTestCase
{
    /** @var GridBuilderController */
    protected $sut;

    /** @var TranslatorInterface */
    protected $translator;
    /** @var ContaoFramework */
    protected $framework;
    /** @var GridStartManipulator */
    protected $gridStartManipulator;

    protected function setUp(): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('contao.resources_paths', realpath(__DIR__.'/../../../tests/_fake_contao'));
        // $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('database_connection', $this->createConfiguredMock(Connection::class, [
            'getDatabasePlatform' => $this->createMock(MySQLPlatform::class),
        ]));

        \Contao\System::setContainer($container);
        $this->getTempDir();

        $mock = $this->mockClassWithProperties(Contao\ContentModel::class);
        $mock->id = 2;
        $mock->title = 'Home';
        $mock->type = 'grid-start';

        $adapter = $this->mockAdapter(['findOneById']);
        $adapter
            ->method('findOneById')
            ->willReturn($mock)
        ;

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->framework = $this->mockContaoFramework([Contao\ContentModel::class => $adapter]);
        $this->gridStartManipulator = $this->createMock(GridStartManipulator::class);

        $this->sut = new GridBuilderController(
            $this->framework,
            $this->translator,
            $this->gridStartManipulator
        );
    }

    public function testsInvoke(): void
    {
        Input::setGet('property', 'cols');
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'success');

        Input::setGet('property', 'rows');
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'success');

        Input::setGet('property', 'classes');
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'success');

        Input::setGet('property', 'grid_cols');
        Input::setGet('grid', 2);
        Input::setGet('value', '4');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'success');

        Input::setGet('property', 'grid_cols');
        Input::setGet('grid', 2);
        Input::setGet('value', '');
        Input::setGet('breakpoint', 'xs');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'success');

        Input::setGet('property', 'foo');
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->__invoke();
        $this->assertSame(json_decode($response->getContent(), true)['status'], 'error');
        $this->assertSame(json_decode($response->getContent(), true)['message'], 'Unknown property');
    }

    public function testSaveCols(): void
    {
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->saveCols();
        $this->assertSame($response['status'], 'success');

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            Input::setGet('breakpoint', null);
            $response = $this->sut->saveCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No breakpoint provided');
        }

        try {
            Input::setGet('id', null);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No element ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', null);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', null);
            $this->sut->saveCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }

    public function testSaveRows(): void
    {
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        Input::setGet('breakpoint', 'xl');
        $response = $this->sut->saveRows();
        $this->assertSame($response['status'], 'success');

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            Input::setGet('breakpoint', null);
            $response = $this->sut->saveRows();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No breakpoint provided');
        }

        try {
            Input::setGet('id', null);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveRows();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No element ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', null);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveRows();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', null);
            $this->sut->saveRows();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }

    public function testSaveClasses(): void
    {
        Input::setGet('id', 1);
        Input::setGet('grid', 2);
        Input::setGet('value', 'col-span-2');
        $response = $this->sut->saveClasses();
        $this->assertSame($response['status'], 'success');

        try {
            Input::setGet('id', null);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveClasses();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No element ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', null);
            Input::setGet('value', 'col-span-2');
            $this->sut->saveClasses();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', null);
            $this->sut->saveClasses();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }

    public function testSaveGridCols(): void
    {
        Input::setGet('grid', 2);
        Input::setGet('breakpoint', 'all');
        Input::setGet('value', '2');
        $response = $this->sut->saveGridCols();
        $this->assertSame($response['status'], 'success');

        try {
            Input::setGet('grid', null);
            Input::setGet('breakpoint', 'all');
            Input::setGet('value', '2');
            $this->sut->saveGridCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('grid', 2);
            Input::setGet('breakpoint', null);
            Input::setGet('value', '2');
            $this->sut->saveGridCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No breakpoint provided');
        }

        try {
            Input::setGet('grid', 2);
            Input::setGet('breakpoint', 'all');
            Input::setGet('value', null);
            $this->sut->saveGridCols();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }

    public function testValidateMandatoryGridItemParameters(): void
    {
        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->validateMandatoryGridItemParameters();
        } catch (Exception $e) {
            $this->assertTrue(false, 'We should never reach this test');
        }

        try {
            Input::setGet('id', null);
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->validateMandatoryGridItemParameters();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No element ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', null);
            Input::setGet('value', 'col-span-2');
            $this->sut->validateMandatoryGridItemParameters();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('id', 1);
            Input::setGet('grid', 2);
            Input::setGet('value', null);
            $this->sut->validateMandatoryGridItemParameters();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }

    public function testValidateMandatoryGridParameters(): void
    {
        try {
            Input::setGet('grid', 2);
            Input::setGet('value', 'col-span-2');
            $this->sut->validateMandatoryGridParameters();
        } catch (Exception $e) {
            $this->assertTrue(false, 'We should never reach this test');
        }

        try {
            Input::setGet('grid', 2);
            Input::setGet('value', '');
            $this->sut->validateMandatoryGridParameters();
        } catch (Exception $e) {
            $this->assertTrue(false, 'We should never reach this test');
        }

        try {
            Input::setGet('grid', null);
            Input::setGet('value', 'col-span-2');
            $this->sut->validateMandatoryGridParameters();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No grid ID provided');
        }

        try {
            Input::setGet('grid', 2);
            Input::setGet('value', null);
            $this->sut->validateMandatoryGridParameters();
            $this->assertTrue(false, 'We should never reach this test');
        } catch (Exception $e) {
            $this->assertSame(\get_class($e), Exception::class);
            $this->assertSame($e->getMessage(), 'No value provided');
        }
    }
}
