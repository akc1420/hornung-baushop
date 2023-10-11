<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Mailing;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\Contracts\DefaultMailingService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingContent;

/**
 * Class MailingService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Mailing
 */
class MailingService implements DefaultMailingService
{
    /**
     * Provides default mailing name.
     *
     * @return string Default mailing name.
     */
    public function getName(): string
    {
        return 'My first Shopware 6 email';
    }

    /**
     * Provides default mailing subject.
     *
     * @return string Default mailing subject.
     */
    public function getSubject(): string
    {
        return 'This is my first newsletter with CleverReach';
    }

    /**
     * Provides default mailing content.
     *
     * @return MailingContent Content of the default mailing.
     */
    public function getContent(): MailingContent
    {
        $content = new MailingContent();
        $content->setType('html\text');
        $content->setText('');
        $content->setHtml('');

        return $content;
    }
}