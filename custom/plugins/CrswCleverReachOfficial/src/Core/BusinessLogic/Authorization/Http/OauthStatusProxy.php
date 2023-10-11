<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\ConnectionStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class OauthStatusProxy extends Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Finishes oauth process.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function finishOauth()
    {
        $this->post('oauth/finish.json', $this->getFinishParameters());
    }

    /**
     * Checks if oauth parameters are still valid.
     *
     * @return bool
     */
    public function isOauthCredentialsValid()
    {
        return $this->getConnectionStatus()->isConnected();
    }

    /**
     * Validates connection to the CleverReach
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\ConnectionStatus
     */
    public function getConnectionStatus()
    {
        try {
            $this->get('debug/validate.json');

            return new ConnectionStatus(true);
        } catch (\Exception $e) {
            return new ConnectionStatus(false, $e->getMessage());
        }
    }

    /**
     * Retrieves finish parameters.
     *
     * @return array
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     */
    private function getFinishParameters()
    {
        return array(
            'finished' => true,
            'name' => $this->authService->getUserInfo()->getFirstName() ?: 'User',
            'brand' => $this->getConfigService()->getIntegrationName(),
            'client_id' => $this->getConfigService()->getClientId(),
        );
    }

    /**
     * Retrieves configuration service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration
     */
    private function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}