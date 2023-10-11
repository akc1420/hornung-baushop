<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Config\Subscriber;

use Pickware\DalBundle\EntityManager;
use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\ShippingBundle\Config\ConfigException;
use Pickware\ShippingBundle\Config\ConfigService;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigDefinition;
use Pickware\ShippingBundle\Config\Model\ShippingMethodConfigEntity;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoadedEvent;
use Shopware\Storefront\Page\Address\Listing\AddressListingPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigureShippingMethodInStorefrontSubscriber implements EventSubscriberInterface
{
    private EntityManager $entityManager;
    private ConfigService $configService;

    public function __construct(EntityManager $entityManager, ConfigService $configService)
    {
        $this->entityManager = $entityManager;
        $this->configService = $configService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutRegisterPageLoadedEvent::class => 'onPageWithShippingMethodConfigDependencyLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onPageWithShippingMethodConfigDependencyLoaded',
            AddressListingPageLoadedEvent::class => 'onPageWithShippingMethodConfigDependencyLoaded',
            AddressDetailPageLoadedEvent::class => 'onPageWithShippingMethodConfigDependencyLoaded',
            AccountLoginPageLoadedEvent::class => 'onPageWithShippingMethodConfigDependencyLoaded',
        ];
    }

    public function onPageWithShippingMethodConfigDependencyLoaded(PageLoadedEvent $event): void
    {
        // Check that dhl was configured completely before adding any dhl extensions to the storefront
        $dhlConfig = new DhlConfig($this->configService->getConfigForSalesChannel(
            DhlConfig::CONFIG_DOMAIN,
            $event->getSalesChannelContext()->getSalesChannelId(),
        ));
        try {
            $dhlConfig->assertConfigurationIsComplete();
        } catch (ConfigException $e) {
            return;
        }

        /** @var ShippingMethodConfigEntity[] $shippingMethodConfigurations */
        $shippingMethodConfigurations = $this->entityManager->findAll(
            ShippingMethodConfigDefinition::class,
            $event->getContext(),
        )->getElements();

        $configurationsByShippingMethodId = [];
        foreach ($shippingMethodConfigurations as $configuration) {
            $configurationsByShippingMethodId[$configuration->getShippingMethodId()] = $configuration;
        }

        $dhlConfigurationPageExtension = new DhlConfigurationPageExtension();
        $dhlConfigurationPageExtension->assign([
            'shippingMethodConfigurations' => $configurationsByShippingMethodId,
            'dhlConfig' => $dhlConfig,
        ]);

        $event->getPage()->addExtension(
            DhlConfigurationPageExtension::PAGE_EXTENSION_NAME,
            $dhlConfigurationPageExtension,
        );
    }
}
