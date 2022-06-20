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

namespace WEM\GridBundle\Controller;

use Contao\ContentModel;
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
class GridBuilderController extends \Contao\Controller
{
    /** @var TranslatorInterface */
    protected $translator;
    /** @var ContaoFramework */
    protected $framework;
    /** @var GridStartManipulator */
    protected $gridStartManipulator;

    public function __construct(
        ContaoFramework $framework,
        TranslatorInterface $translator,
        GridStartManipulator $gridStartManipulator
    ) {
        $this->framework = $framework;
        $this->translator = $translator;
        $this->gridStartManipulator = $gridStartManipulator;
        $this->framework->initialize();
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
                default:
                    throw new Exception('Unknown property');
            }
        } catch (\Exception $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return new Response(json_encode($response));
    }

    public function saveCols(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryParameters();
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

    public function saveRows(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryParameters();
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

    public function saveClasses(): array
    {
        $response = ['status' => 'success', 'message' => ''];
        $this->validateMandatoryParameters();
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

    public function validateMandatoryParameters(): void
    {
        if (null === Input::get('id')) {
            throw new Exception('No element ID provided');
        }
        if (null === Input::get('grid')) {
            throw new Exception('No grid ID provided');
        }
        if (null === Input::get('value')) {
            throw new Exception('No value provided');
        }
    }

    protected function getGridStart(int $id): ContentModel
    {
        $grid = $this->framework->getAdapter(ContentModel::class)->findOneById($id); // to allow Unit Tests to run
        if (!$grid) {
            throw new Exception('No grid found with the provided ID');
        }

        return $grid;
    }
}
