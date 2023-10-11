<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks;

class EventRegistrationResultRecorder extends SubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $service = $this->getEventsService();
        $result = $this->getExecutionContext()->getEventResult();

        $service->setSecret($result->getSecret());
        $service->setCallToken($result->getCallToken());

        $this->reportProgress(100);
    }
}