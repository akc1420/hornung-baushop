<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Contracts;

/**
 * Interface DefaultMailingService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Contracts
 */
interface DefaultMailingService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Provides default mailing name.
     *
     * @return string Default mailing name.
     */
    public function getName();

    /**
     * Provides default mailing subject.
     *
     * @return string Default mailing subject.
     */
    public function getSubject();

    /**
     * Provides default mailing content.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingContent Content of the default mailing.
     */
    public function getContent();
}