<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\LocationFinder\Request;

use GuzzleHttp\Psr7\Request;

class FindLocationsByCoordinatesRequest extends Request
{
    private const STANDARD_RADIUS_IN_METERS = 2500;
    private const STANDARD_LIMIT = 50;
    private const STANDARD_PROVIDER = 'parcel';

    private float $latitude;
    private float $longitude;
    private int $radiusInMeters;

    public function __construct(float $latitude, float $longitude, ?int $radiusInMeters)
    {
        if (!$radiusInMeters) {
            $radiusInMeters = self::STANDARD_RADIUS_IN_METERS;
        }

        parent::__construct(
            'GET',
            sprintf('find-by-geo?%s', http_build_query(
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $radiusInMeters,
                    'limit' => self::STANDARD_LIMIT,
                    'providerType' => self::STANDARD_PROVIDER,
                ],
            )),
        );

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radiusInMeters = $radiusInMeters;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getRadiusInMeters(): int
    {
        return $this->radiusInMeters;
    }
}
