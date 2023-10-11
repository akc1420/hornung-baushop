<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Pipeline\AbandonedCartCreatePipeline;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AbandonedCartEventListener
{
    public function handle(AbandonedCartEvent $event)
    {
        try {
            AbandonedCartCreatePipeline::execute($event->getTrigger());
            $this->getService()->create($event->getTrigger());
        } catch (\Exception $e) {
            Logger::logWarning($e->getMessage(), 'Core', array('trace' => $e->getTraceAsString()));
        }
    }

    /**
     * Retrieves abandoned cart record service.
     *
     * @return AbandonedCartRecordService | object
     */
    private function getService()
    {
        return ServiceRegister::getService(AbandonedCartRecordService::CLASS_NAME);
    }
}