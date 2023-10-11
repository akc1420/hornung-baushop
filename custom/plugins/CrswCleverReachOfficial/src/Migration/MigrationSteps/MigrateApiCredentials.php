<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\OauthStatusProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Migration\Exceptions\ApiCredentialsNotPresentException;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;
use Exception;

/**
 * Class MigrateApiCredentials
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class MigrateApiCredentials extends Step
{
    /**
     * Migrate user API credentials.
     *
     * @throws ApiCredentialsNotPresentException
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        $apiCredentials = V2Repository::getAPICredentials();

        if ($apiCredentials === [] || count($apiCredentials) !== 3) {
            throw new APICredentialsNotPresentException('API credentials are not present.');
        }

        $authInfo = new AuthInfo(
            $apiCredentials[0]['value'],
            $apiCredentials[2]['value'],
            $apiCredentials[1]['value']
        );

        try {
            $this->getAuthService()->setAuthInfo($authInfo);
        } catch (Exception $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute migration step because: ' . $e->getMessage()
            );
        }

        $connectionStatus = $this->getOauthStatusProxy()->getConnectionStatus();

        if (!$connectionStatus->isConnected()) {
            $this->getAuthService()->getFreshOfflineStatus();
        }
    }

    /**
     * @return AuthorizationService
     */
    private function getAuthService(): AuthorizationService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(AuthorizationService::class);
    }

    /**
     * @return OauthStatusProxy
     */
    private function getOauthStatusProxy(): OauthStatusProxy
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(OauthStatusProxy::class);
    }
}