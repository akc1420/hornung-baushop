<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Adapter;

use DateTime;
use DateTimeImmutable;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\AdditionalInsuranceServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\BulkyGoodsServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\CashOnDeliveryServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\ClosestDroppointDeliveryServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\EnclosedReturnLabelOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\EndorsementServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\IdentCheckServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\NamedPersonOnlyServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\NoNeighbourDeliveryServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\ParcelOutletRoutingServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PostalDeliveryDutyPaidServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PreferredDayServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PreferredLocationServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PreferredNeighbourServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PremiumServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\PrintOnlyIfCodeableOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\SignedForByRecipientServiceOption;
use Pickware\PickwareDhl\Adapter\ShipmentOrder\Options\VisualCheckOfAgeServiceOption;
use Pickware\PickwareDhl\ApiClient\DhlProduct;
use Pickware\PickwareDhl\Config\DhlConfig;
use Pickware\ShippingBundle\Config\ConfigException;

class DhlShipmentConfig
{
    private array $shipmentConfig;

    public function __construct(array $shipmentConfig)
    {
        $this->shipmentConfig = $shipmentConfig;
    }

    public function getShipmentOrderOptions(DhlConfig $dhlConfig): array
    {
        $shipmentOrderOptions = [];
        if (isset($this->shipmentConfig['bulkyGoods']) && $this->shipmentConfig['bulkyGoods']) {
            $shipmentOrderOptions[] = new BulkyGoodsServiceOption();
        }
        if (isset($this->shipmentConfig['enclosedReturnLabel']) && $this->shipmentConfig['enclosedReturnLabel']) {
            $shipmentOrderOptions[] = new EnclosedReturnLabelOption($dhlConfig->getBillingInformation());
        }
        if (isset($this->shipmentConfig['printOnlyIfCodeable']) && $this->shipmentConfig['printOnlyIfCodeable']) {
            $shipmentOrderOptions[] = new PrintOnlyIfCodeableOption();
        }
        if (isset($this->shipmentConfig['namedPersonOnly']) && $this->shipmentConfig['namedPersonOnly']) {
            $shipmentOrderOptions[] = new NamedPersonOnlyServiceOption();
        }
        if (isset($this->shipmentConfig['visualCheckOfAge'])
            && in_array(
                intval($this->shipmentConfig['visualCheckOfAge']),
                VisualCheckOfAgeServiceOption::SUPPORTED_AGES,
                true,
            )
        ) {
            $shipmentOrderOptions[] = new VisualCheckOfAgeServiceOption(
                intval($this->shipmentConfig['visualCheckOfAge']),
            );
        }
        if (isset($this->shipmentConfig['additionalInsurance'])
            && is_numeric($this->shipmentConfig['additionalInsurance'])
            && floatval($this->shipmentConfig['additionalInsurance']) > 0
        ) {
            $shipmentOrderOptions[] = new AdditionalInsuranceServiceOption(
                floatval($this->shipmentConfig['additionalInsurance']),
            );
        }
        if (isset($this->shipmentConfig['codEnabled']) && $this->shipmentConfig['codEnabled']) {
            if (!isset($this->shipmentConfig['codAmount'])) {
                throw ConfigException::missingConfigurationField(DhlConfig::CONFIG_DOMAIN, 'codAmount');
            }

            $shipmentOrderOptions[] = new CashOnDeliveryServiceOption(
                $dhlConfig->getBankTransferData(),
                (float) $this->shipmentConfig['codAmount'],
                true,
            );
        }
        if (isset($this->shipmentConfig['identCheckEnabled']) && $this->shipmentConfig['identCheckEnabled']) {
            $dateOfBirth = DateTime::createFromFormat('Y-m-d', $this->shipmentConfig['identCheckDateOfBirth'] ?? '');
            if (!$dateOfBirth) {
                throw DhlAdapterException::shipmentConfigIsMissingDateOfBirthOrInWrongFormat();
            }

            $shipmentOrderOptions[] = new IdentCheckServiceOption(
                $this->shipmentConfig['identCheckGivenName'],
                $this->shipmentConfig['identCheckSurname'],
                $dateOfBirth,
                intval($this->shipmentConfig['identCheckMinimumAge']),
            );
        }
        if (isset($this->shipmentConfig['preferredDay']) && $this->shipmentConfig['preferredDay'] !== '') {
            $shipmentOrderOptions[] = new PreferredDayServiceOption(
                DateTimeImmutable::createFromFormat('Y-m-d', $this->shipmentConfig['preferredDay']),
            );
        }
        if (isset($this->shipmentConfig['preferredNeighbour']) && $this->shipmentConfig['preferredNeighbour'] !== '') {
            $shipmentOrderOptions[] = new PreferredNeighbourServiceOption($this->shipmentConfig['preferredNeighbour']);
        }
        if (isset($this->shipmentConfig['preferredLocation']) && $this->shipmentConfig['preferredLocation'] !== '') {
            $shipmentOrderOptions[] = new PreferredLocationServiceOption($this->shipmentConfig['preferredLocation']);
        }
        if (isset($this->shipmentConfig['noNeighbourDelivery']) && $this->shipmentConfig['noNeighbourDelivery']) {
            $shipmentOrderOptions[] = new NoNeighbourDeliveryServiceOption();
        }
        if (isset($this->shipmentConfig['endorsement']) && $this->shipmentConfig['endorsement'] !== '') {
            $shipmentOrderOptions[] = new EndorsementServiceOption($this->shipmentConfig['endorsement']);
        }
        if (isset($this->shipmentConfig['parcelOutletRouting']) && $this->shipmentConfig['parcelOutletRouting']) {
            $shipmentOrderOptions[] = new ParcelOutletRoutingServiceOption();
        }
        if (isset($this->shipmentConfig['premium']) && $this->shipmentConfig['premium']) {
            $shipmentOrderOptions[] = new PremiumServiceOption();
        }
        if (isset($this->shipmentConfig['postalDeliveryDutyPaid']) && $this->shipmentConfig['postalDeliveryDutyPaid']) {
            $shipmentOrderOptions[] = new PostalDeliveryDutyPaidServiceOption();
        }
        if (isset($this->shipmentConfig['closestDroppointDelivery']) && $this->shipmentConfig['closestDroppointDelivery']) {
            $shipmentOrderOptions[] = new ClosestDroppointDeliveryServiceOption();
        }
        if (isset($this->shipmentConfig['signedForByRecipient']) && $this->shipmentConfig['signedForByRecipient']) {
            $shipmentOrderOptions[] = new SignedForByRecipientServiceOption();
        }

        return $shipmentOrderOptions;
    }

    public function getProduct(): DhlProduct
    {
        $productCode = $this->shipmentConfig['product'] ?? '';
        if (!DhlProduct::isValidProductCode($productCode)) {
            throw DhlAdapterException::invalidProductCode($productCode);
        }

        return DhlProduct::getByCode($productCode);
    }

    public function getTermsOfTrade(): ?string
    {
        if (isset($this->shipmentConfig['createExportDocuments']) && $this->shipmentConfig['createExportDocuments']) {
            if (!isset($this->shipmentConfig['incotermInternational']) && !isset($this->shipmentConfig['incotermEurope'])) {
                throw DhlAdapterException::shipmentConfigIsMissingTermsOfTrade();
            }

            return $this->shipmentConfig['incotermInternational'] ?? $this->shipmentConfig['incotermEurope'];
        }

        return null;
    }

    public function getExportDocumentsActive(): bool
    {
        return (bool) ($this->shipmentConfig['createExportDocuments'] ?? false);
    }
}
