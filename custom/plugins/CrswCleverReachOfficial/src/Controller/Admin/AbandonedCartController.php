<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AbandonedCartController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AbandonedCartController extends AbstractController
{
    /**
     * @var AutomationService
     */
    private $automationService;

    /**
     * AbandonedCartController constructor.
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
     * @Route(path="api/v{version}/cleverreach/shops/get", name="api.cleverreach.shops.get", methods={"GET"})
     * @Route(path="/api/cleverreach/shops/get", name="api.cleverreach.shops.get.new", methods={"GET"})
     *
     */
    public function getShops(): JsonApiResponse
    {
        $activeShops = $this->automationService->getActiveShops();
        $result = [];

        foreach ($activeShops as $activeShop) {
            try {
                $cart = $this->automationService->get($activeShop->getId());
                $automationId = $cart ? $cart->getId() : 'none';

                $result[] = [
                    'id' => $activeShop->getId(),
                    'automationId' => $automationId,
                    'shopName' => $activeShop->getName(),
                    'state' => ($cart && $cart->getStatus() === 'created') ? 'active' : 'inactive'
                ];
            } catch (BaseException $e) {
                Logger::logError($e->getMessage());
            }
        }

        return new JsonApiResponse(['shopsData' => $result, 'notification' => $this->getNotification()]);
    }

    /**
     * @return bool
     */
    private function getNotification(): bool
    {
        try {
            $notification = ConfigurationManager::getInstance()->getConfigValue('AbandonedCartNotification', false);

            if ($notification) {
                ConfigurationManager::getInstance()->saveConfigValue('AbandonedCartNotification', false);
            }

            return $notification;
        } catch (QueryFilterInvalidParamException $e) {
            return false;
        }
    }
}
