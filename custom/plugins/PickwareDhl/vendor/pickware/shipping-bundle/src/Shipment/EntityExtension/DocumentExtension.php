<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\Shipment\EntityExtension;

use Pickware\DocumentBundle\Model\DocumentDefinition;
use Pickware\ShippingBundle\Shipment\Model\DocumentShipmentMappingDefinition;
use Pickware\ShippingBundle\Shipment\Model\DocumentTrackingCodeMappingDefinition;
use Pickware\ShippingBundle\Shipment\Model\ShipmentDefinition;
use Pickware\ShippingBundle\Shipment\Model\TrackingCodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class DocumentExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'pickwareShippingTrackingCodes',
                TrackingCodeDefinition::class,
                DocumentTrackingCodeMappingDefinition::class,
                'document_id',
                'tracking_code_id',
            ))->addFlags(new CascadeDelete()),
        );
        $collection->add(
            (new ManyToManyAssociationField(
                'pickwareShippingShipments',
                ShipmentDefinition::class,
                DocumentShipmentMappingDefinition::class,
                'document_id',
                'shipment_id',
            ))->addFlags(new CascadeDelete()),
        );
    }

    public function getDefinitionClass(): string
    {
        return DocumentDefinition::class;
    }
}
