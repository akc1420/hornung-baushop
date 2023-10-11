<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\LocationFinder;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class LocationFinderResponseProcessor
{
    public function processResponse(ResponseInterface $response, $allowedLocationType = null): array
    {
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $responseJson = json_decode(
                (string) $response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } else {
            return [];
        }

        $locations = [];
        foreach ($responseJson['locations'] as $location) {
            $parsedLocation = null;
            switch ($location['location']['type']) {
                case 'locker':
                    $parsedLocation = ProviderLocation::createPackstationLocation($location['location']['keywordId']);
                    break;
                case 'servicepoint':
                    $parsedLocation = ProviderLocation::createPaketshopLocation($location['location']['keywordId']);
                    break;
                case 'postbank':
                case 'postoffice':
                    $parsedLocation = ProviderLocation::createPostofficeLocation($location['location']['keywordId']);
                    break;
            }

            if ($parsedLocation === null
                || ($allowedLocationType !== null && $parsedLocation->getType() !== $allowedLocationType)
                || $parsedLocation->getNumber() === ''
            ) {
                continue;
            }

            $idsByProvider = [];
            foreach ($location['location']['ids'] as $dhlLocationId) {
                $idsByProvider[$dhlLocationId['provider']] = $dhlLocationId['locationId'];
            }
            $parsedLocation->setIdsByProvider($idsByProvider);
            $parsedLocation->setOpeningHoursByDay($this->getOpeningHoursByDays($location['openingHours']));
            $parsedLocation->setPlace($location['place']);
            $parsedLocation->setServiceTypes($location['serviceTypes']);
            $parsedLocation->setName($location['name']);

            $locations[] = $parsedLocation;
        }

        return $locations;
    }

    private function getOpeningHoursByDays(array $openingHours): array
    {
        $openingHoursByDays = [];
        foreach ($openingHours as $openingHour) {
            // The day of week is specified by its schema.org url, for example 'http://schema.org/Monday'
            $lowercaseDay = mb_strtolower(str_replace('http://schema.org/', '', $openingHour['dayOfWeek']));
            $openingHoursByDays[$lowercaseDay][] = $openingHour;
        }

        return $openingHoursByDays;
    }
}
