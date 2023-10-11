<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks;

class EventRegistrator extends SubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $result = $this->getEventsProxy()->registerEvent($this->getExecutionContext()->getEvent());
        $this->getExecutionContext()->setEventResult($result);

        $this->reportProgress(100);
    }
}