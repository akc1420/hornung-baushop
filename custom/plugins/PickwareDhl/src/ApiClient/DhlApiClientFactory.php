<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Pickware\HttpUtils\Sanitizer\AuthHttpSanitizer;
use Pickware\HttpUtils\Sanitizer\HttpSanitizing;
use Pickware\PickwareDhl\ApiClient\Handler\DhlBcpApiErrorHandlingRequestHandler;
use Pickware\PickwareDhl\ApiClient\Handler\DhlHttpSanitizer;
use Pickware\PickwareDhl\ApiClient\Handler\DhlVersionSoapRequestHandler;
use Pickware\ShippingBundle\Http\HttpLogger;
use Pickware\ShippingBundle\Rest\GuzzleLoggerMiddleware;
use Pickware\ShippingBundle\Rest\RestApiClient;
use Pickware\ShippingBundle\Soap\RequestHandler\AntiCompressionSoapRequestHandler;
use Pickware\ShippingBundle\Soap\RequestHandler\SoapRequestLoggingHandler;
use Pickware\ShippingBundle\Soap\SoapApiClient;
use Psr\Log\LoggerInterface;
use SoapClient;
use SoapHeader;

class DhlApiClientFactory
{
    public const API_VERSION_MAJOR = 3;
    public const API_VERSION_MINOR = 5;
    public const API_VERSION_PATCH = 0;
    public const API_VERSION_BETA = false;
    public const API_VERSION_STRING = self::API_VERSION_MAJOR . '.' . self::API_VERSION_MINOR . '.' . self::API_VERSION_PATCH . (self::API_VERSION_BETA ? '-BETA' : '');

    private const PRODUCTION_BASE_URL = 'https://cig.dhl.de/services/production';

    private const TEST_BASE_URL = 'https://cig.dhl.de/services/sandbox';
    private const TEST_USER = '2222222222_01';
    private const TEST_PASSWORD = 'pass';

    private LoggerInterface $dhlRequestLogger;
    private string $productionUser;
    private string $productionPassword;

    public function __construct(LoggerInterface $dhlRequestLogger, string $productionUser, string $productionPassword)
    {
        $this->dhlRequestLogger = $dhlRequestLogger;
        $this->productionUser = $productionUser;
        $this->productionPassword = $productionPassword;
    }

    public function createDhlBcpApiClient(DhlApiClientConfig $dhlApiClientConfig): SoapApiClient
    {
        $bcpWsdlFileName = sprintf(
            '%s/WsdlDocuments/gkp/%s/geschaeftskundenversand-api-%s.wsdl',
            __DIR__,
            self::API_VERSION_STRING,
            self::API_VERSION_STRING,
        );
        $soapApiClient = $this->createDhlSoapClient($bcpWsdlFileName, $dhlApiClientConfig);

        $soapApiClient->use(
            new DhlVersionSoapRequestHandler(self::API_VERSION_MAJOR, self::API_VERSION_MINOR),
            new AntiCompressionSoapRequestHandler(),
            new DhlBcpApiErrorHandlingRequestHandler(),
            new SoapRequestLoggingHandler($soapApiClient->getSoapClient(), $this->dhlRequestLogger, new DhlHttpSanitizer()),
        );

        return $soapApiClient;
    }

    public function createDhlParcelManagementApiClient(DhlApiClientConfig $dhlApiClientConfig): RestApiClient
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->unshift(new GuzzleLoggerMiddleware(new HttpLogger(
            $this->dhlRequestLogger,
            new HttpSanitizing(new AuthHttpSanitizer()),
        )));

        return $this->createDhlRestClient($dhlApiClientConfig, $handlerStack);
    }

    protected function createDhlSoapClient(string $wsdlFileName, DhlApiClientConfig $dhlApiConfig): SoapApiClient
    {
        $soapClient = $this->createSoapClient($wsdlFileName, $this->getSoapOptions($dhlApiConfig));
        $soapClient->__setSoapHeaders($this->getSoapHeaders($dhlApiConfig));

        return new SoapApiClient($soapClient);
    }

    protected function createDhlRestClient(DhlApiClientConfig $dhlApiConfig, ?HandlerStack $handlerStack = null): RestApiClient
    {
        $dhlApiConfig->getConfig()->assertNotEmpty('customerNumber');

        $restClient = $this->createRestClient([
            'base_uri' => sprintf(
                '%s/rest/',
                $dhlApiConfig->shouldUseTestingEndpoint() ? self::TEST_BASE_URL : self::PRODUCTION_BASE_URL,
            ),
            'auth' => [
                $dhlApiConfig->shouldUseTestingEndpoint() ? $dhlApiConfig->getUsername() : $this->productionUser,
                $dhlApiConfig->shouldUseTestingEndpoint() ? $dhlApiConfig->getPassword() : $this->productionPassword,
            ],
            'handler' => $handlerStack ?? HandlerStack::create(),
            'headers' => [
                'X-EKP' => $dhlApiConfig->getConfig()['customerNumber'],
            ],
            'allow_redirects' => true,
        ]);

        return new RestApiClient($restClient);
    }

    protected function createSoapClient(string $wsdlFileName, array $soapOptions): SoapClient
    {
        return new SoapClient($wsdlFileName, $soapOptions);
    }

    protected function createRestClient(array $config): Client
    {
        return new Client($config);
    }

    private function getSoapOptions(DhlApiClientConfig $dhlApiConfig): array
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
        ];

        if ($dhlApiConfig->shouldUseTestingEndpoint()) {
            // Use the DHL developer portal login of the config
            $extraOptions = [
                'login' => $dhlApiConfig->getUsername(),
                'password' => $dhlApiConfig->getPassword(),
                'location' => sprintf('%s/soap', self::TEST_BASE_URL),
            ];
        } else {
            // Production settings
            $extraOptions = [
                'login' => $this->productionUser,
                'password' => $this->productionPassword,
                'location' => sprintf('%s/soap', self::PRODUCTION_BASE_URL),
            ];
        }

        return array_merge($options, $extraOptions);
    }

    /**
     * @return SoapHeader[]
     */
    private function getSoapHeaders(DhlApiClientConfig $dhlApiConfig): array
    {
        if ($dhlApiConfig->shouldUseTestingEndpoint()) {
            $username = self::TEST_USER;
            $password = self::TEST_PASSWORD;
        } else {
            // DHL BCP API accepts username in lowercase format only
            $username = mb_strtolower($dhlApiConfig->getUsername());
            $password = $dhlApiConfig->getPassword();
        }

        $auth = [
            'user' => $username,
            'signature' => $password,
            'type' => 0,
        ];
        $authHeader = new SoapHeader('http://dhl.de/webservice/cisbase', 'Authentification', $auth, false);

        return [$authHeader];
    }
}
