<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\UserProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;
use Exception;

/**
 * Class SaveUserInfo
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class SaveUserInfo extends Step
{
    /**
     * Save user info.
     *
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        try {
            if ($this->getAuthService()->isOffline()) {
                $user = json_decode(V2Repository::getUserInfo(), true);
                $userInfo = UserInfo::fromArray($user);
                $this->getAuthService()->setUserInfo($userInfo);

                return;
            }

            $userInfo = $this->getUserProxy()->getUserInfo();
            $userInfo->setLanguage($this->getUserProxy()->getUserLanguage($userInfo->getId() ?: 'en'));
            $this->getAuthService()->setUserInfo($userInfo);
        } catch (Exception $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute SaveUserInfo step because: ' . $e->getMessage()
            );
        }
    }

    /**
     * @return UserProxy
     */
    private function getUserProxy(): UserProxy
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UserProxy::class);
    }

    /**
     * @return AuthorizationService
     */
    private function getAuthService(): AuthorizationService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AuthorizationService::class);
    }
}