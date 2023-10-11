<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class AuthInfo
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\DTO
 */
class AuthInfo extends DataTransferObject
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var string Access Token.
     */
    protected $accessToken;
    /**
     * @var string Refresh Token.
     */
    protected $refreshToken;
    /**
     * @var int Access Token duration.
     */
    protected $accessTokenDuration;

    /**
     * AuthInfo constructor.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @param int $accessTokenDuration
     */
    public function __construct($accessToken, $refreshToken, $accessTokenDuration)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->accessTokenDuration = $accessTokenDuration;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return int
     */
    public function getAccessTokenDuration()
    {
        return $this->accessTokenDuration;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @param int $accessTokenDuration
     */
    public function setAccessTokenDuration($accessTokenDuration)
    {
        $this->accessTokenDuration = $accessTokenDuration;
    }

    /**
     * Creates instance of AuthInfo.
     *
     * @param array $data
     *
     * @return AuthInfo Instance of AuthInfo.
     */
    public static function fromArray(array $data)
    {
        return new self($data['access_token'], $data['refresh_token'], $data['expires_in']);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'access_token' => $this->getAccessToken(),
            'refresh_token' => $this->getRefreshToken(),
            'expires_in' => $this->getAccessTokenDuration(),
        );
    }
}