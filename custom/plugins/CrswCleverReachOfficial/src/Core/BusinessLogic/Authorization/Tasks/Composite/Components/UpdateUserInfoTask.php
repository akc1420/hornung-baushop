<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\UserProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduledTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class UpdateUserInfoTask extends ScheduledTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Updates user info.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $userInfo = $this->getProxy()->getUserInfo();

        $this->reportProgress(30);

        $userInfo->setLanguage($this->getProxy()->getUserLanguage($userInfo->getId()));

        $this->reportProgress(80);

        $this->getAuthService()->setUserInfo($userInfo);

        $this->reportProgress(100);
    }

    /**
     * Retrieves User Proxy.
     *
     * @return UserProxy
     */
    private function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UserProxy::CLASS_NAME);
    }

    /**
     * Retrieves authorization service.
     *
     * @return AuthorizationService
     */
    private function getAuthService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AuthorizationService::CLASS_NAME);
    }
}