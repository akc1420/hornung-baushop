<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\AuthorizationService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\RegistrationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\AuthorizationEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\ConnectionLostEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\OauthStatusProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\RefreshProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

abstract class AuthorizationService implements BaseService
{
    /**
     * @param string $lang
     *
     * @param bool $isRefresh
     *
     * @return string
     */
    public function getAuthIframeUrl($lang = 'en', $isRefresh = false)
    {
        $registerData = $this->getRegistrationService()->getData();
        $authUrl = Proxy::BASE_API_URL . 'oauth/authorize.php';
        $parameters = array(
            'response_type' => 'code',
            'grant' => 'basic',
            'client_id' => $this->getConfigService()->getClientId(),
            'redirect_uri' => $this->getRedirectURL($isRefresh),
            'bg' => $this->getAuthIframeColor(),
            'lang' => $lang,
        );

        if (!empty($registerData)) {
            $parameters['registerdata'] = $registerData;
        }

        $authUrl .= '?' . http_build_query($parameters);

        if ($isRefresh) {
            $authUrl .= '#login';
        }

        return $authUrl;
    }

    /**
     * Retrieves color code of authentication iframe background.
     *
     * @return string
     *     Color code.
     */
    public function getAuthIframeColor()
    {
        return 'ffffff';
    }

    /**
     * Retrieves valid auth info.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getAuthInfo()
    {
        $savedInfo = $this->getConfigurationManager()->getConfigValue('authInfo', array());
        if (empty($savedInfo)) {
            throw new FailedToRetrieveAuthInfoException('Failed to retrieve auth info.');
        }

        $authInfo = AuthInfo::fromArray($savedInfo);

        if (time() >= $authInfo->getAccessTokenDuration()) {
            $authInfo = $this->refreshAuthInfo($authInfo);
        }

        return $authInfo;
    }

    /**
     * Sets auth info.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo $authInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setAuthInfo($authInfo = null)
    {
        $this->getConfigurationManager()->saveConfigValue('authInfo', $authInfo ? $authInfo->toArray() : null);
    }

    /**
     * Retrieves user info.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     */
    public function getUserInfo()
    {
        $savedAuthInfo = $this->getConfigurationManager()->getConfigValue('userInfo', array());

        if (empty($savedAuthInfo)) {
            throw new FailedToRetrieveUserInfoException('Failed to retrieve user info.');
        }

        return UserInfo::fromArray($savedAuthInfo);
    }

    /**
     * Sets user info.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo $userInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setUserInfo($userInfo = null)
    {
        $this->getConfigurationManager()->saveConfigValue('userInfo', $userInfo ? $userInfo->toArray() : null);
    }

    /**
     * Saves user offline status.
     *
     * @param bool $isOffline
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function setIsOffline($isOffline)
    {
        if (!$this->isOffline() && $isOffline) {
            AuthorizationEventBus::getInstance()->fire(new ConnectionLostEvent());
        }

        $this->getConfigurationManager()->saveConfigValue('isOffline', $isOffline);
    }

    /**
     * Provides cashed value for the offline mode status.
     *
     * @NOTE This value can be outdated. For fresh value please @see getFreshOfflineStatus
     *
     * @return bool Flag that indicates whether the user is offline or not.
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function isOffline()
    {
        return (bool)$this->getConfigurationManager()->getConfigValue('isOffline', false);
    }

    /**
     * Attempts to refresh offline status for the user. Provides refreshed offline mode status.
     *
     * @NOTE Refresh implies TWO API calls and ONE database write.
     *       This operation can have HIGH performance impact.
     *       For more performant option @see isOffline.
     *
     * The offline status will be refreshed only if the CleverReach API is available.
     *
     * @return boolean
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function getFreshOfflineStatus()
    {
        if ($this->getApiStatusProxy()->isAPIActive()) {
            $status = $this->getOauthStatusProxy()->isOauthCredentialsValid();
            $this->setIsOffline(!$status);
        }

        return $this->isOffline();
    }

    /**
     * Refreshes auth info.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo $authInfo
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    protected function refreshAuthInfo(AuthInfo $authInfo)
    {
        try {
            $authInfo = $this->getRefreshProxy()->refreshAuthInfo($authInfo->getRefreshToken());
        } catch (\Exception $e) {
            $this->setIsOffline(true);
            throw new FailedToRefreshAccessToken($e->getMessage());
        }

        $this->setIsOffline(false);
        $this->setAuthInfo($authInfo);

        return $authInfo;
    }

    /**
     * Retrieves configuration manager.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager Configuration Manager instance.
     */
    protected function getConfigurationManager()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }

    /**
     * Retrieves Refresh proxy.
     *
     * @return RefreshProxy
     */
    protected function getRefreshProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(RefreshProxy::CLASS_NAME);
    }

    /**
     * Retrieves RegistrationService
     *
     * @return RegistrationService
     */
    protected function getRegistrationService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(RegistrationService::CLASS_NAME);
    }

    /**
     * Retrieves Configuration
     *
     * @return Configuration
     */
    protected function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Provides oauth status proxy.
     *
     * @return OauthStatusProxy | object
     */
    protected function getOauthStatusProxy()
    {
        return ServiceRegister::getService(OauthStatusProxy::CLASS_NAME);
    }

    /**
     * Provides api status proxy.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\API\Http\Proxy | object
     */
    protected function getApiStatusProxy()
    {
        return ServiceRegister::getService(\Crsw\CleverReachOfficial\Core\BusinessLogic\API\Http\Proxy::CLASS_NAME);
    }
}