<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Carrier;

use Pickware\ShippingBundle\ParcelPacking\ParcelPackingConfiguration;

class Carrier
{
    private string $technicalName;
    private string $name;
    private string $abbreviation;
    private string $configDomain;
    private ?string $shipmentConfigDescriptionFilePath;
    private ?string $storefrontConfigDescriptionFilePath;
    private ?string $returnShipmentConfigDescriptionFilePath;
    private ?ParcelPackingConfiguration $defaultParcelPackingConfiguration;
    private ?string $returnLabelMailTemplateTechnicalName;
    private int $batchSize;

    public function __construct(
        string $technicalName,
        string $name,
        string $abbreviation,
        string $configDomain,
        string $shipmentConfigDescriptionFilePath = null,
        string $storefrontConfigDescriptionFilePath = null,
        string $returnShipmentConfigDescriptionFilePath = null,
        ?ParcelPackingConfiguration $defaultParcelPackingConfiguration = null,
        string $returnLabelMailTemplateTechnicalName = null,
        int $batchSize = 1
    ) {
        $this->technicalName = $technicalName;
        $this->name = $name;
        $this->abbreviation = $abbreviation;
        $this->configDomain = $configDomain;
        $this->shipmentConfigDescriptionFilePath = $shipmentConfigDescriptionFilePath;
        $this->storefrontConfigDescriptionFilePath = $storefrontConfigDescriptionFilePath;
        $this->returnShipmentConfigDescriptionFilePath = $returnShipmentConfigDescriptionFilePath;
        $this->defaultParcelPackingConfiguration = $defaultParcelPackingConfiguration;
        $this->returnLabelMailTemplateTechnicalName = $returnLabelMailTemplateTechnicalName;
        $this->batchSize = $batchSize;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    public function getConfigDomain(): string
    {
        return $this->configDomain;
    }

    public function getShipmentConfigDescription(): ConfigDescription
    {
        if ($this->shipmentConfigDescriptionFilePath === null) {
            return ConfigDescription::createEmpty();
        }

        return ConfigDescription::readFromYamlFile($this->shipmentConfigDescriptionFilePath);
    }

    public function getStorefrontConfigDescription(): ConfigDescription
    {
        if ($this->storefrontConfigDescriptionFilePath === null) {
            return ConfigDescription::createEmpty();
        }

        return ConfigDescription::readFromYamlFile($this->storefrontConfigDescriptionFilePath);
    }

    public function getReturnShipmentConfigDescription(): ConfigDescription
    {
        if ($this->returnShipmentConfigDescriptionFilePath === null) {
            return ConfigDescription::createEmpty();
        }

        return ConfigDescription::readFromYamlFile($this->returnShipmentConfigDescriptionFilePath);
    }

    public function getDefaultParcelPackingConfiguration(): ParcelPackingConfiguration
    {
        if ($this->defaultParcelPackingConfiguration === null) {
            return ParcelPackingConfiguration::createDefault();
        }

        return $this->defaultParcelPackingConfiguration;
    }

    public function getReturnLabelMailTemplateTechnicalName(): ?string
    {
        return $this->returnLabelMailTemplateTechnicalName;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
