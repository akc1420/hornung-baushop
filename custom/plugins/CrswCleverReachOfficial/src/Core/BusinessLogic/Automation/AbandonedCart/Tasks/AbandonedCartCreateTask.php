<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartSubmit;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class AbandonedCartCreateTask extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates abandoned cart chain on clever reach.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Exceptions\FailedToCreateAbandonedCartException
     */
    public function execute()
    {
        $service = $this->getService();
        $storeId = $service->getStoreId();
        $cartData = new AbandonedCartSubmit($service->getAutomationName(), $storeId);
        $cartData->setSource($this->getConfigService()->getIntegrationName());

        $cart = $service->create($cartData);
        $this->reportProgress(70);
        $entityService = $this->getEntityService();
        $entityService->set($cart);
        $entityService->setStoreId($storeId);
        $this->reportProgress(100);
    }

    /**
     * Retrieves abandoned cart service.
     *
     * @return AbandonedCartService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartService::CLASS_NAME);
    }

    /**
     * Retrieves abandoned cart entity service.
     *
     * @return AbandonedCartEntityService | object
     */
    private function getEntityService()
    {
        return ServiceRegister::getService(AbandonedCartEntityService::CLASS_NAME);
    }
}