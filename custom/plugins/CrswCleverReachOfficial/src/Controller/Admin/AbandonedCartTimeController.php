<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AbandonedCartTimeController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AbandonedCartTimeController extends AbstractController
{
    /**
     * @var AutomationService
     */
    private $automationService;

    /**
     * AbandonedCartTimeController constructor.
     *
     * @param Initializer $initializer
     * @param AutomationService $automationService
     */
    public function __construct(Initializer $initializer, AutomationService $automationService)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->automationService = $automationService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/actime/get", name="api.cleverreach.actime.get", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/actime/get", name="api.cleverreach.actime.get.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function getTime(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $cart = $this->automationService->get($shopId);
            $result = $cart ? (string)$cart->getSettings()['delay'] : '10';

            return new JsonApiResponse(['time' => $result]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['time' => '10']);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/actime/save",
     *     name="api.cleverreach.actime.save", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/actime/save",
     *     name="api.cleverreach.actime.save.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function saveTime(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');
        $time = $request->get('time');

        try {
            $this->automationService->updateDelay($shopId, $time);

            return new JsonApiResponse(['success' => true]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['success' => false]);
        }
    }
}
