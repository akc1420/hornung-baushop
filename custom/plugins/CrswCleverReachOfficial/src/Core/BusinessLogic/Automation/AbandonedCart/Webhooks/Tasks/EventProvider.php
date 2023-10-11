<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts\AbandonedCartEntityService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Random\RandomString;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\SubTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class EventProvider extends SubTask
{
    const CLASS_NAME = __CLASS__;

    public function execute()
    {
        $service = $this->getEventsService();

        $event = new Event();
        $event->setEvent($service->getType());
        $event->setUrl($service->getEventUrl());
        $event->setGroupId($this->getEntityService()->get()->getId());

        $token = $service->getVerificationToken();
        if (empty($token)) {
            $token = RandomString::generate();
            $service->setVerificationToken($token);
        }

        $event->setVerificationToken($token);

        $this->getExecutionContext()->setEvent($event);
        $this->reportProgress(100);
    }

    /**
     * @return AbandonedCartEntityService | object
     */
    private function getEntityService()
    {
        return ServiceRegister::getService(AbandonedCartEntityService::CLASS_NAME);
    }
}