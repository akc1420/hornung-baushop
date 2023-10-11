<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigEntity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ConfigService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration
 */
class ConfigService extends Configuration
{
    public const INTEGRATION_NAME = 'Shopware 6';
    public const CLIENT_ID = 'n4VcLY2We5';
    public const CLIENT_SECRET = 'xUWd13GnxnXHmKy6dL1qvpvqBgpEdDKK';
    public const DEFAULT_QUEUE_NAME = 'Shopware 6 - Default';
    public const MIN_LOG_LEVEL = 2;

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName(): string
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * Returns async process starter url, always in http.
     *
     * @param string $guid Process identifier.
     *
     * @return string Formatted URL of async process starter endpoint.
     */
    public function getAsyncProcessUrl($guid): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);
        // for support controller
        if ($guid === '/') {
            $guid = '0';
        }

        $routeName = 'api.cleverreach.async.new';
        $params = ['guid' => $guid];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.cleverreach.async';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()
            ->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Returns default queue name
     *
     * @return string default queue name
     */
    public function getDefaultQueueName(): string
    {
        return self::DEFAULT_QUEUE_NAME;
    }

    /**
     * Retrieves client id of the integration.
     *
     * @return string
     */
    public function getClientId(): string
    {
        return self::CLIENT_ID;
    }

    /**
     * Retrieves client secret of the integration.
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return self::CLIENT_SECRET;
    }

    /**
     * Returns base url of the integrated system.
     *
     * @return string Url.
     */
    public function getSystemUrl(): string
    {
        return $_ENV['APP_URL'];
    }

    /**
     * Gets allOrdersSynced config value.
     *
     * @return ConfigEntity | bool
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getAllOrdersSynced()
    {
        return $this->getConfigurationManager()->getConfigValue('allOrdersSynced', false);
    }

    /**
     * Sets allOrdersSynced config value.
     *
     * @throws QueryFilterInvalidParamException
     */
    public function setAllOrdersSynced(): void
    {
        $this->getConfigurationManager()->saveConfigValue('allOrdersSynced', true);
    }

    /**
     * Saves admin language.
     *
     * @param string $language
     *
     * @throws QueryFilterInvalidParamException
     */
    public function saveAdminLanguage(string $language): void
    {
        $this->getConfigurationManager()->saveConfigValue('adminLanguage', $language);
    }

    /**
     * Gets admin language.
     *
     * @return string|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getAdminLanguage(): ?string
    {
        return $this->getConfigurationManager()->getConfigValue('adminLanguage');
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getUrlGenerator(): UrlGeneratorInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UrlGeneratorInterface::class);
    }
}
