<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\EventRegisterResult;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\EventRegistrator as BaseTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;

/**
 * Class EventRegistrator
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Webhooks\Tasks
 */
class EventRegistrator extends BaseTask
{
    const CLASS_NAME = __CLASS__;

    public function execute()
    {
        try {
            parent::execute();
        } catch (HttpRequestException $exception) {
            Logger::logWarning("Failed to register event: {$exception->getMessage()}", 'Core');
            $this->setExistingResult();
        }

        $this->reportProgress(100);
    }

    /**
     * Set existing call token and secret as fallback
     */
    protected function setExistingResult()
    {
        $result = new EventRegisterResult();
        $result->setCallToken($this->getEventsService()->getCallToken());
        $result->setSecret($this->getEventsService()->getSecret());

        $this->getExecutionContext()->setEventResult($result);
    }
}
