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

namespace WEM\GridBundle\Controller;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use WEM\GridBundle\Classes\GridStartManipulator;

/**
 * @Route("/contao/grid-builder",
 *     name=GridBuilderController::class,
 *     defaults={"_scope": "backend"}
 * )
 * @ServiceTag("controller.service_arguments")
 */
class GridBuilderController extends Controller
{

    protected TranslatorInterface $translator;

    protected ContaoFramework $framework;

    protected GridStartManipulator $gridStartManipulator;

    public function __construct(
        ContaoFramework $framework,
        TranslatorInterface $translator,
        GridStartManipulator $gridStartManipulator
    ) {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->gridStartManipulator = $gridStartManipulator;
        $this->framework->initialize();
        Parent::__construct();
    }

    public function __invoke(): Response
    {
        try {
            switch (Input::get('property')) {
                case 'cols':
                    $response = $this->saveCols();
                break;
                case 'rows':
                    $response = $this->saveRows();
                break;
                case 'classes':
                    $response = $this->saveClasses();
                break;
                case 'grid_cols':
                    $response = $this->saveGridCols();
                break;
                default:
                    throw new Exception('Unknown property');
            }
        } catch (\Exception $exception) {
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }

        return new Response(json_encode($response));
    }

    /**
     * @throws Exception
     */
    public function saveCols(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryGridItemParameters();
        if (null === Input::get('breakpoint')) {
            throw new Exception('No breakpoint provided');
        }

        $grid = $this->getGridStart((int) Input::get('grid'));
        $gsm = $this->gridStartManipulator->setGridStart($grid);
        $gsm->setGridStart($grid);
        $gsm->setGridItemsSettingsForItemAndPropertyAndResolution((int) Input::get('id'), GridStartManipulator::PROPERTY_COLS, Input::get('breakpoint'), Input::get('value'));

        $grid = $gsm->getGridStart();
        $grid->save();

        $gsm->recalculateElementsForAllGridSharingTheSamePidAndPtable();
        $grid = $gsm->getGridStart();
        $grid->save();

        return $response;
    }

    /**
     * @throws Exception
     */
    public function saveRows(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryGridItemParameters();
        if (null === Input::get('breakpoint')) {
            throw new Exception('No breakpoint provided');
        }

        $grid = $this->getGridStart((int) Input::get('grid'));
        $gsm = $this->gridStartManipulator->setGridStart($grid);
        $gsm->setGridStart($grid);
        $gsm->setGridItemsSettingsForItemAndPropertyAndResolution((int) Input::get('id'), GridStartManipulator::PROPERTY_ROWS, Input::get('breakpoint'), Input::get('value'));

        $grid = $gsm->getGridStart();
        $grid->save();

        $gsm->recalculateElementsForAllGridSharingTheSamePidAndPtable();
        $grid = $gsm->getGridStart();
        $grid->save();

        return $response;
    }

    /**
     * @throws Exception
     */
    public function saveClasses(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryGridItemParameters();
        $grid = $this->getGridStart((int) Input::get('grid'));
        $gsm = $this->gridStartManipulator->setGridStart($grid);
        $gsm->setGridStart($grid);
        $gsm->setGridItemsSettingsForItemAndPropertyAndResolution((int) Input::get('id'), GridStartManipulator::PROPERTY_CLASSES, null, Input::get('value'));

        $grid = $gsm->getGridStart();
        $grid->save();

        $gsm->recalculateElementsForAllGridSharingTheSamePidAndPtable();
        $grid = $gsm->getGridStart();
        $grid->save();

        return $response;
    }

    /**
     * @throws Exception
     */
    public function saveGridCols(): array
    {
        $response = ['status' => 'success', 'message' => ''];

        $this->validateMandatoryGridParameters();
        if (null === Input::get('breakpoint')) {
            throw new Exception('No breakpoint provided');
        }

        $value = Input::get('value');
        $value = empty($value) ? null : (int) $value;

        $grid = $this->getGridStart((int) Input::get('grid'));
        $gsm = $this->gridStartManipulator->setGridStart($grid);

        $gsm->setGridStart($grid);
        switch (strtolower(Input::get('breakpoint'))) {
            case 'all':
                $gsm->setGridColsAll($value);
            break;
            case 'xl':
                $gsm->setGridColsXl($value);
            break;
            case 'lg':
                $gsm->setGridColsLg($value);
            break;
            case 'md':
                $gsm->setGridColsMd($value);
            break;
            case 'sm':
                $gsm->setGridColsSm($value);
            break;
            case 'xs':
                $gsm->setGridColsXs($value);
            break;
            case 'xxs':
                $gsm->setGridColsXxs($value);
            break;
        }

        $grid = $gsm->getGridStart();
        $grid->save();

        return $response;
    }

    /**
     * @throws Exception
     */
    public function validateMandatoryGridItemParameters(): void
    {
        $this->validateMandatoryGridParameters();
        if (null === Input::get('id')) {
            throw new Exception('No element ID provided');
        }
    }

    /**
     * @throws Exception
     */
    public function validateMandatoryGridParameters(): void
    {
        if (null === Input::get('grid')) {
            throw new Exception('No grid ID provided');
        }

        if (null === Input::get('value')) {
            throw new Exception('No value provided');
        }
    }

    /**
     * @throws Exception
     */
    protected function getGridStart(int $id): ContentModel
    {
        $grid = $this->framework->getAdapter(ContentModel::class)->findOneById($id); // to allow Unit Tests to run
        if (!$grid) {
            throw new Exception('No grid found with the provided ID');
        }

        return $grid;
    }
}
