<?php declare(strict_types=1);
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

namespace Gbmed\Form\Storefront;

use Shopware\Core\Content\ContactForm\Event\ContactFormEvent;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailService implements EventSubscriberInterface
{
    private $data = [];
    private SystemConfigService $systemConfigService;

    /**
     * MailService constructor.
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MailBeforeSentEvent::class => 'onMailBeforeSentEvent',
            ContactFormEvent::EVENT_NAME => 'onContactFormEvent',
        ];
    }

    /**
     * @param MailBeforeSentEvent $event
     */
    public function onMailBeforeSentEvent(MailBeforeSentEvent $event): void
    {
        $mail = $event->getMessage();
        if (!$this->systemConfigService->getBool('GbmedForm.config.changeFrom') || !isset($this->data['email'])) {
            return;
        }

        $mail->from($this->data['email']);
    }

    /**
     * @param ContactFormEvent $event
     */
    public function onContactFormEvent(ContactFormEvent $event): void
    {
        $this->data = $event->getContactFormData();
    }
}
