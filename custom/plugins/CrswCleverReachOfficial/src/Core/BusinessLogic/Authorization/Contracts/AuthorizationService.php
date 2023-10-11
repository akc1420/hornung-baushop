<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\AuthInfo;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO\UserInfo;

/**
 * Interface AuthorizationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts
 */
interface AuthorizationService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves authorization redirect url.
     *
     * @param bool $isRefresh Specifies whether url is retrieved for token refresh.
     *
     * @return string Authorization redirect url.
     */
    public function getRedirectURL($isRefresh = false);

    /**
     * Retrieves authorization iframe url.
     *
     * @param string $lang shop admin language
     *
     * @param bool $isRefresh Specifies whether url is retrieved for token refresh.
     *
     * @return string Authorization iframe url.
     */
    public function getAuthIframeUrl($lang = 'en', $isRefresh = false);

    /**
     * Retrieves color code of authentication iframe background.
     *
     * @return string
     *     Color code.
     */
    public function getAuthIframeColor();

    /**
     * Retrieves auth info object for the current user
     *
     * @return AuthInfo Instance of auth info object.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     */
    public function getAuthInfo();

    /**
     * Sets auth info for the current user.
     *
     * @param AuthInfo $authInfo Auth info object instance.
     */
    public function setAuthInfo($authInfo = null);

    /**
     * Retrieves user info object for the current user.
     *
     * @return UserInfo
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveUserInfoException
     */
    public function getUserInfo();

    /**
     * Sets user info for the current user.
     *
     * @param UserInfo $userInfo User info object instance.
     */
    public function setUserInfo($userInfo = null);

    /**
     * Provides cashed value for the offline mode status.
     *
     * @NOTE This value can be outdated. For fresh value please @see getFreshOfflineStatus
     *
     * @return bool Flag that indicates whether the user is offline or not.
     */
    public function isOffline();

    /**
     * Sets the offline mode status for the current user.
     *
     * @param bool $isOffline Flag that indicates current user's offline mode status.
     *
     * @return void
     */
    public function setIsOffline($isOffline);

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
     */
    public function getFreshOfflineStatus();
}