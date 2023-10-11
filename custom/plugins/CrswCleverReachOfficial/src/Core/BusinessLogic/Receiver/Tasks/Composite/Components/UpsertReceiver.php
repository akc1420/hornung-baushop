<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

class UpsertReceiver extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Sends changed receiver data to CleverReach.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $context = $this->getExecutionContext();

        if ($context->receiver !== null) {
            $this->getReceiverProxy()->upsertPlus($context->groupId, array($context->receiver));
        }

        $this->reportProgress(100);
    }
}