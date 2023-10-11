<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\OauthStatusProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class CompleteAuthTask extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Completes oauth process.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $this->getProxy()->finishOauth();
        $this->reportProgress(100);
    }

    /**
     * Retrieves oauth status proxy.
     *
     * @return OauthStatusProxy
     */
    private function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(OauthStatusProxy::CLASS_NAME);
    }
}