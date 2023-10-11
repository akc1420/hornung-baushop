<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\HttpClient;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

class AuthProxy extends Proxy
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
     * @param string $code
     * @param string $redirectUrl
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getAuthInfo($code, $redirectUrl)
    {
        $response = $this->post('oauth/token.php', $this->getAuthParameters($code, $redirectUrl));
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
        return array();
    }

    /**
     * Retrieves auth info parameters.
     *
     * @param $code
     * @param $redirectUrl
     *
     * @return array
     */
    protected function getAuthParameters($code, $redirectUrl)
    {
        return array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->getConfigService()->getClientId(),
            'client_secret' => $this->getConfigService()->getClientSecret(),
            'code' => $code,
            'redirect_uri' => urlencode($redirectUrl),
        );
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
     * Retrieves configuration service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration
     */
    protected function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }
}