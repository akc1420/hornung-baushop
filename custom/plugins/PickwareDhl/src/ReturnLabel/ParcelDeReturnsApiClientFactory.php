<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ReturnLabel;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Pickware\HttpUtils\Sanitizer\AuthHttpSanitizer;
use Pickware\HttpUtils\Sanitizer\HttpSanitizing;
use Pickware\PickwareDhl\ApiClient\DhlApiClientConfig;
use Pickware\PickwareDhl\ApiClient\DhlApiClientException;
use Pickware\ShippingBundle\Http\HttpLogger;
use Pickware\ShippingBundle\Rest\BadResponseExceptionHandlingMiddleware;
use Pickware\ShippingBundle\Rest\GuzzleLoggerMiddleware;
use Pickware\ShippingBundle\Rest\RestApiClient;
use Psr\Log\LoggerInterface;

class ParcelDeReturnsApiClientFactory
{
    private const PRODUCTION_BASE_URL = 'https://api-eu.dhl.com/parcel/de/shipping/returns/v1/';
    private const TEST_BASE_URL = 'https://api-sandbox.dhl.com/parcel/de/shipping/returns/v1/';
    private const TEST_USER = '2222222222_customer';
    private const TEST_PASSWORD = 'uBQbZ62!ZiBiVVbhc';
    private const API_KEY = 'rjCF1ScqbBMBRNsjTmzz5zKvP2cyrsAR';
    private LoggerInterface $dhlRequestLogger;

    public function __construct(LoggerInterface $dhlRequestLogger)
    {
        $this->dhlRequestLogger = $dhlRequestLogger;
    }

    public function createApiClient(DhlApiClientConfig $dhlApiConfig): RestApiClient
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->unshift(new BadResponseExceptionHandlingMiddleware(
            [
                DhlApiClientException::class,
                'fromClientException',
            ],
            [
                DhlApiClientException::class,
                'fromServerException',
            ],
        ));
        $handlerStack->unshift(new GuzzleLoggerMiddleware(new HttpLogger(
            $this->dhlRequestLogger,
            new HttpSanitizing(new AuthHttpSanitizer()),
        )));

        $restClient = $this->createRestClient([
            'base_uri' => $dhlApiConfig->shouldUseTestingEndpoint() ? self::TEST_BASE_URL : self::PRODUCTION_BASE_URL,
            'auth' => [
                $dhlApiConfig->shouldUseTestingEndpoint() ? self::TEST_USER : $dhlApiConfig->getUsername(),
                $dhlApiConfig->shouldUseTestingEndpoint() ? self::TEST_PASSWORD : $dhlApiConfig->getPassword(),
            ],
            'handler' => $handlerStack,
            'headers' => ['dhl-api-key' => self::API_KEY],
        ]);

        return new RestApiClient($restClient);
    }

    protected function createRestClient(array $config): Client
    {
        return new Client($config);
    }
}
