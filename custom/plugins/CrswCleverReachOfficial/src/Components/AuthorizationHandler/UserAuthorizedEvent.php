<?php

namespace Crsw\CleverReachOfficial\Components\AuthorizationHandler;

use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Events\Event;

/**
 * Class UserAuthorizedEvent
 *
 * @package Crsw\CleverReachOfficial\Components\AuthorizationHandler
 */
class UserAuthorizedEvent extends Event
{
    /**
     * @var bool
     */
    private $isReAuth;

    /**
     * UserAuthorizedEvent constructor.
     *
     * @param $isReAuth
     */
    public function __construct($isReAuth)
    {
        $this->isReAuth = $isReAuth;
    }

    /**
     * @return bool
     */
    public function isReAuth(): bool
    {
        return $this->isReAuth;
    }
}