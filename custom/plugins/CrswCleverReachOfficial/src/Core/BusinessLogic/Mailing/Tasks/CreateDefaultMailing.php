<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Contracts\DefaultMailingService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\Mailing;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class CreateDefaultMailing
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Tasks
 */
class CreateDefaultMailing extends Task
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates default mailing.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $hasMailing = $this->getProxy()->hasMailing();

        $this->reportProgress(20);

        if (!$hasMailing) {
            $this->getProxy()->createMailing($this->getDefaultMailing());
        }

        $this->reportProgress(100);
    }

    /**
     * Provides default mailing.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\Mailing
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     */
    protected function getDefaultMailing()
    {
        $service = $this->getDefaultMailingService();

        $mailing = new Mailing();
        $mailing->setName($service->getName());
        $mailing->setSubject($service->getSubject());

        // This option is disabled until the client specifies
        // Template suitable for usage in integrations.

        // $mailing->setContent($service->getContent());
        $userInfo = $this->getAuthService()->getUserInfo();
        $mailing->setSenderName($userInfo->getCompany() ?: $userInfo->getFirstName() ?: 'User');
        $mailing->setSenderEmail($userInfo->getEmail());

        return $mailing;
    }

    /**
     * Provides mailing proxy.
     *
     * @return Proxy | object
     */
    protected function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Provides default mailing service.
     *
     * @return DefaultMailingService | object
     */
    protected function getDefaultMailingService()
    {
        return ServiceRegister::getService(DefaultMailingService::CLASS_NAME);
    }

    /**
     * Provides authorization service.
     *
     * @return AuthorizationService | object
     */
    protected function getAuthService()
    {
        return ServiceRegister::getService(AuthorizationService::CLASS_NAME);
    }

    /**
     * Provides group service.
     *
     * @return GroupService | object
     */
    protected function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }
}