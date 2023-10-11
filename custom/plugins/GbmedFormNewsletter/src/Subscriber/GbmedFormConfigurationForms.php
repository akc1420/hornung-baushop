<?php
/**
 * Example plugin to extend Shopware 6 plugin GbmedForm
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedFormNewsletter
 * @copyright      Copyright (c) 2020, gb media
 */

declare(strict_types=1);

namespace Gbmed\FormNewsletter\Subscriber;

use Gbmed\Form\Framework\Captcha\FormRoutes\GbmedFormConfigurationFormsEvent;
use Gbmed\FormNewsletter\Framework\Captcha\FormRoutes\Newsletter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GbmedFormConfigurationForms implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GbmedFormConfigurationFormsEvent::class => 'onGbmedFormConfigurationFormsEvent'
        ];
    }

    public function onGbmedFormConfigurationFormsEvent(GbmedFormConfigurationFormsEvent $event)
    {
        $config = $this->getConfig($event->getSalesChannelContext());

        if($config['newsletter']){
            $event->addExtensionForms(Newsletter::NAME);
        }
    }

    private function getConfig(SalesChannelContext $salesChannelContext): array
    {
        /** @var array $config */
        $config = $this->systemConfigService->get(
            'GbmedFormNewsletter.config',
            $salesChannelContext->getSalesChannel()->getId()
        );

        return $config;
    }
}
