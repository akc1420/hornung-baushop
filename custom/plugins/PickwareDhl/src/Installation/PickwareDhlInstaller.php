<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Installation;

use Doctrine\DBAL\Connection;
use Pickware\DalBundle\EntityManager;
use Pickware\InstallationLibrary\MailTemplate\MailTemplateInstaller;
use Pickware\InstallationLibrary\MailTemplate\MailTemplateUninstaller;
use Pickware\PickwareDhl\ReturnLabel\ReturnLabelMailTemplate;
use Pickware\ShippingBundle\Installation\CarrierInstaller;
use Pickware\ShippingBundle\Installation\CarrierUninstaller;
use Pickware\ShippingBundle\Installation\CustomFieldSetInstaller;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PickwareDhlInstaller
{
    private MailTemplateInstaller $mailTemplateInstaller;
    private MailTemplateUninstaller $mailTemplateUninstaller;
    private CarrierInstaller $carrierInstaller;
    private CarrierUninstaller $carrierUninstaller;
    private CustomFieldSetInstaller $customFieldSetInstaller;
    private LoggerInterface $businessEventsLogger;

    private function __construct()
    {
        // Create an instance with ::initFromContainer()
    }

    public static function initFromContainer(ContainerInterface $container): self
    {
        $self = new self();
        /** @var Connection $db */
        $db = $container->get(Connection::class);
        $entityManager = new EntityManager($container, $db, null);
        $self->mailTemplateInstaller = new MailTemplateInstaller($entityManager);
        $self->mailTemplateUninstaller = new MailTemplateUninstaller($entityManager);
        $self->carrierInstaller = new CarrierInstaller($db);
        $self->carrierUninstaller = CarrierUninstaller::createForContainer($container);
        $self->customFieldSetInstaller = new CustomFieldSetInstaller($db);
        $self->businessEventsLogger = $container->get('monolog.logger.business_events');

        return $self;
    }

    public function postInstall(Context $context): void
    {
        $this->postUpdate($context);
    }

    public function postUpdate(Context $context): void
    {
        $this->mailTemplateInstaller->installMailTemplate(
            new ReturnLabelMailTemplate(),
            $this->businessEventsLogger,
            $context,
        );

        // The carrier will reference the mail template type, therefore create them after the mail templates
        $this->carrierInstaller->installCarrier(new DhlCarrier());

        $this->customFieldSetInstaller->installCustomFieldSet([
            'name' => 'pickware_dhl_preferred_delivery_set',
            'active' => 1,
            'config' => [
                'label' => [
                    'en-GB' => 'DHL Preferred delivery',
                    'de-DE' => 'DHL Wunschpaket',
                ],
            ],
            'customFields' => [
                [
                    'name' => 'pickware_dhl_preferred_day',
                    'type' => CustomFieldTypes::DATETIME,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Preferred day',
                            'de-DE' => 'Wunschtag',
                        ],
                    ],
                ],
                [
                    'name' => 'pickware_dhl_preferred_location',
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Preferred location',
                            'de-DE' => 'Wunschort',
                        ],
                    ],
                ],
                [
                    'name' => 'pickware_dhl_preferred_neighbour',
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Preferred neighbour',
                            'de-DE' => 'Wunschnachbar',
                        ],
                    ],
                ],
                [
                    'name' => 'pickware_dhl_no_neighbour_delivery',
                    'type' => CustomFieldTypes::BOOL,
                    'config' => [
                        'type' => 'checkbox',
                        'label' => [
                            'en-GB' => 'No neighbour delivery',
                            'de-DE' => 'Keine Nachbarschaftszustellung',
                        ],
                    ],
                ],
            ],
        ], [OrderDefinition::ENTITY_NAME]);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->carrierUninstaller->uninstallCarrier(DhlCarrier::TECHNICAL_NAME);
        $this->mailTemplateUninstaller->uninstallMailTemplate(new ReturnLabelMailTemplate(), $uninstallContext->getContext());
    }
}
