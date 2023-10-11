<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AbandonedCartStatusController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AbandonedCartStatusController extends AbstractController
{
    /**
     * @var AutomationService
     */
    private $automationService;

    /**
     * AbandonedCartStatusController constructor.
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
     * @Route(path="api/v{version}/cleverreach/abandonedcart/status/index",
     *     name="api.cleverreach.abandonedcart.status.index", methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/abandonedcart/status/index", name="api.cleverreach.abandonedcart.status.index.new", methods={"GET", "POST"})

     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function index(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $cart = $this->automationService->get($shopId);

            $result = $cart ? $cart->getStatus() === 'created' : false;
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            $result = false;
        }

        return new JsonApiResponse(['status' => $result]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/status/enable",
     *     name="api.cleverreach.abandonedcart.status.enable", methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/abandonedcart/status/enable", name="api.cleverreach.abandonedcart.status.enable.new", methods={"GET", "POST"})

     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function enable(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $this->automationService->create($shopId);
            ConfigurationManager::getInstance()->saveConfigValue('AbandonedCartNotification', true);

            return new JsonApiResponse(['status' => 'enabled']);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['error' => $e->getMessage()]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/status/disable",
     *     name="api.cleverreach.abandonedcart.status.disable", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/abandonedcart/status/disable",
     *     name="api.cleverreach.abandonedcart.status.disable.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function disable(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $this->automationService->delete($shopId);

            return new JsonApiResponse(['status' => 'disabled']);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['error' => $e->getMessage()]);
        }
    }
}
