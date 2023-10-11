<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class RefreshProxy extends Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * AuthProxy constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct($client, null);
    }

    /**
     * Retrieves users auth info.
     *
     * @param string $refreshToken
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function refreshAuthInfo($refreshToken)
    {
        $response = $this->post('oauth/token.php', $this->getRefreshParameters($refreshToken));
        $authInfo = $response->decodeBodyToArray();
        $authInfo['expires_in'] = $this->getConfigService()->getTokenLifeTime($authInfo['access_token']);

        return AuthInfo::fromArray($authInfo);
    }

    /**
     * Retrieves auth headers.
     *
     * @return array
     */
    protected function getAuthHeaders()
    {
        $identity = base64_encode($this->getConfigService()->getClientId() . ':'
            . $this->getConfigService()->getClientSecret());

        return array('Authorization: Basic '. $identity);
    }

    /**
     * Retrieves configuration service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration
     */
    protected function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Retrieves full request url.
     *
     * @param string $endpoint Endpoint identifier.
     *
     * @return string Full request url.
     */
    protected function getUrl($endpoint)
    {
        return self::BASE_API_URL . ltrim(trim($endpoint), '/');
    }

    /**
     * Retrieves refresh auth info parameters.
     *
     * @param string $refreshToken
     *
     * @return array
     */
    private function getRefreshParameters($refreshToken)
    {
        return array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        );
    }
}