<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\DhlBcpConfigScraper;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Pickware\PickwareDhl\DhlBcpConfigScraper\Exception\AbstractDhlBcpConfigScraperException;
use Pickware\PickwareDhl\DhlBcpConfigScraper\Exception\DhlBcpConfigScraperCommunicationException;
use Pickware\PickwareDhl\DhlBcpConfigScraper\Exception\DhlBcpConfigScraperInvalidCredentialsException;
use Pickware\PickwareDhl\DhlBcpConfigScraper\Exception\DhlBcpConfigScraperUserIsSystemUserException;

/**
 * Communicates with DHL Business Customer Portal (BCP) to check login credentials and scrape configuration for
 * webservice.
 */
class DhlBcpConfigScraper
{
    private const DHL_BCP_URL = 'https://geschaeftskunden.dhl.de/customeradministration/api/v1/';

    private HttpClient $client;
    private string $username;
    private string $password;
    private ?string $accessToken;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;

        if ($this->username === '' || $this->password === '') {
            throw DhlBcpConfigScraperInvalidCredentialsException::usernameOrPasswordMissing();
        }

        $this->client = new HttpClient(['base_uri' => self::DHL_BCP_URL]);
    }

    /**
     * Try to access the "DHL Business Customer Portal" using the provided credentials.
     */
    public function checkCredentials(): CheckCredentialsResult
    {
        try {
            $this->login();

            return CheckCredentialsResult::credentialsAreValid();
        } catch (DhlBcpConfigScraperInvalidCredentialsException $e) {
            return CheckCredentialsResult::credentialsAreInvalid();
        } catch (DhlBcpConfigScraperUserIsSystemUserException $e) {
            return CheckCredentialsResult::userIsSystemUser();
        } catch (AbstractDhlBcpConfigScraperException $e) {
            throw $e;
        } catch (Exception $e) {
            // Wrap any error, because that probably means the website changed and made this component incompatible
            throw DhlBcpConfigScraperCommunicationException::unexpectedError($e);
        }
    }

    /**
     * Access the "DHL Business Customer Portal" using the provided credentials, then read the customer number and the
     * billing numbers from the respective endpoints.
     */
    public function fetchContractData(): DhlContractData
    {
        try {
            $this->login();

            return $this->getContractData();
        } catch (AbstractDhlBcpConfigScraperException $e) {
            throw $e;
        } catch (Exception $e) {
            // Wrap any error, because that probably means the website changed and made this component incompatible
            throw DhlBcpConfigScraperCommunicationException::unexpectedError($e);
        }
    }

    /**
     * Login and save the access token if successful
     */
    private function login(): void
    {
        $loginURI = 'https://sso.geschaeftskunden.dhl.de/auth/realms/GkpExternal/protocol/openid-connect/token';

        try {
            $response = $this->client->request('POST', $loginURI, [
                'form_params' => [
                    'grant_type' => 'password',
                    'username' => $this->username,
                    'password' => $this->password,
                    'client_id' => 'external-frame',
                ],
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                $response = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                if (isset($response['message']['key']) && $response['message']['key'] === 'invalidUserType') {
                    throw new DhlBcpConfigScraperUserIsSystemUserException();
                }

                throw DhlBcpConfigScraperInvalidCredentialsException::loginFailed();
            }

            throw $e;
        }

        $responseArray = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);

        $this->accessToken = $responseArray->access_token;
    }

    /**
     * Retrieve the customerNumber from the user endpoint
     *
     * @return string customerNumber
     */
    private function getCustomerNumber(): string
    {
        $response = $this->client->request('GET', 'user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
        ]);

        $responseArray = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);

        return $responseArray[0]->ekp;
    }

    /**
     * Retrieve the contractData from the customerData endpoint.
     */
    private function getContractData(): DhlContractData
    {
        $customerNumber = $this->getCustomerNumber();

        $response = $this->client->request('GET', 'customerdata/' . $customerNumber, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
        ]);

        $responseArray = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        $contracts = $responseArray->contracts;

        return new DhlContractData(
            $customerNumber,
            $this->extractBookingProductsFromAPI($contracts),
        );
    }

    /**
     * @return array Associative array with values as array's.
     */
    private function extractBookingProductsFromAPI(array $contracts): array
    {
        // Format response to only contain needed information
        $data = [];
        foreach ($contracts as $contract) {
            $accountNumber = $contract->contractBillingNumber;
            $data[$accountNumber] = $contract->bookingText;
        }

        return DhlContractDataBookedProduct::createFromBcpProductNameBillingNumbersMapping($data);
    }
}
