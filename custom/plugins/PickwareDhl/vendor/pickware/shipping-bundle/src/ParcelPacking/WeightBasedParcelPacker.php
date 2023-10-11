<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\ShippingBundle\ParcelPacking;

use Pickware\ShippingBundle\Notifications\NotificationService;
use Pickware\ShippingBundle\Parcel\Parcel;
use Pickware\ShippingBundle\ParcelPacking\BinPacking\BinPackingException;
use Pickware\ShippingBundle\ParcelPacking\BinPacking\WeightBasedBinPacker;

class WeightBasedParcelPacker implements ParcelPacker
{
    private WeightBasedBinPacker $binPacker;
    private NotificationService $notificationService;

    public function __construct(WeightBasedBinPacker $binPacker, NotificationService $notificationService)
    {
        $this->binPacker = $binPacker;
        $this->notificationService = $notificationService;
    }

    /**
     * @return Parcel[]
     */
    public function repackParcel(Parcel $parcel, ParcelPackingConfiguration $parcelPackingConfiguration): array
    {
        $parcel->setFillerWeight($parcelPackingConfiguration->getFillerWeightPerParcel());

        if ($parcel->getTotalWeight() === null) {
            if ($parcelPackingConfiguration->getFallbackParcelWeight()) {
                $parcel->setWeightOverwrite($parcelPackingConfiguration->getFallbackParcelWeight());
            }

            return [$parcel];
        }

        $maxParcelWeight = $parcelPackingConfiguration->getMaxParcelWeight();
        if ($maxParcelWeight === null) {
            return [$parcel];
        }

        $binCapacity = $maxParcelWeight->subtract($parcelPackingConfiguration->getFillerWeightPerParcel());
        try {
            $bins = $this->binPacker->packIntoBins($parcel->getItems(), $binCapacity);
        } catch (BinPackingException $e) {
            $this->notificationService->emit(ParcelPackingNotification::binPackingFailed($e));

            return [$parcel];
        }

        $parcels = [];
        foreach ($bins as $bin) {
            $subParcel = $parcel->createCopyWithoutItems();
            $subParcel->setItems($bin);
            if ($subParcel->getCustomsInformation()) {
                $subParcel->getCustomsInformation()->setFees([]);
            }
            $parcels[] = $subParcel;
        }

        if ($parcel->getCustomsInformation()) {
            $parcels[0]->getCustomsInformation()->setFees($parcel->getCustomsInformation()->getFees());
        }

        return $parcels;
    }
}
