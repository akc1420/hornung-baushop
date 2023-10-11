<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\LocationFinder\Controller;

use Pickware\HttpUtils\ResponseFactory;
use Pickware\PickwareDhl\LocationFinder\LocationFinderApiClientFactory;
use Pickware\PickwareDhl\LocationFinder\LocationFinderResponseProcessor;
use Pickware\PickwareDhl\LocationFinder\Request\FindLocationsByAddressRequest;
use Pickware\PickwareDhl\LocationFinder\Request\FindLocationsByCoordinatesRequest;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class LocationFinderStorefrontController extends StorefrontController
{
    private LocationFinderApiClientFactory $locationFinderApiClientFactory;
    private LocationFinderResponseProcessor $locationFinderResponseProcessor;

    public function __construct(
        LocationFinderApiClientFactory  $locationFinderApiClientFactory,
        LocationFinderResponseProcessor $locationFinderResponseProcessor
    ) {
        $this->locationFinderApiClientFactory = $locationFinderApiClientFactory;
        $this->locationFinderResponseProcessor = $locationFinderResponseProcessor;
    }

    /**
     * @Route("/pickware-dhl/location-finder/locations", name="pickware-dhl.frontend.location-finder.locations", options={"seo"="false"}, methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function getLocations(Request $request): Response
    {
        $locationFinderApiClient = $this->locationFinderApiClientFactory->createApiClient();

        $latitude = (float) $request->get('latitude');
        $longitude = (float) $request->get('longitude');

        if ($latitude && $longitude) {
            $dhlApiRequest = new FindLocationsByCoordinatesRequest(
                $latitude,
                $longitude,
                $request->get('radiusInMeters') ? (int) $request->get('radiusInMeters') : null,
            );
        } else {
            if (!$request->get('zipcode')) {
                return ResponseFactory::createParameterMissingResponse('zipcode');
            }

            $dhlApiRequest = new FindLocationsByAddressRequest($request->get('zipcode'));
        }

        $locations = $this->locationFinderResponseProcessor->processResponse(
            $locationFinderApiClient->sendRequest($dhlApiRequest),
            $request->get('allowedLocationType'),
        );

        return new JsonResponse($locations);
    }
}
