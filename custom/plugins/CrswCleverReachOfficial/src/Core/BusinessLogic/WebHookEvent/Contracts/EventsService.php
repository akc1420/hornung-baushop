<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts;

/**
 * Interface EventsService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts
 */
interface EventsService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Provides url that will listen to web hook requests.
     *
     * @return string
     */
    public function getEventUrl();

    /**
     * Provides event type. One of [form | receiver]
     *
     * @return string
     */
    public function getType();

    /**
     * Provides call token.
     *
     * @return string
     */
    public function getCallToken();

    /**
     * Sets call token.
     *
     * @param $token
     *
     * @return void
     */
    public function setCallToken($token);

    /**
     * Provides secret.
     *
     * @return string
     */
    public function getSecret();

    /**
     * Sets secret.
     *
     * @param $secret
     *
     * @return void
     */
    public function setSecret($secret);

    /**
     * Provides event verification used during the process of event registration.
     *
     * @return string
     */
    public function getVerificationToken();

    /**
     * Sets event verification token.
     *
     * @param string $token
     *
     * @return void
     */
    public function setVerificationToken($token);
}