<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Support;

use Composer\InstalledVersions;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole\SupportService as BaseSupportService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall\UninstallService;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class SupportService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Support
 */
class SupportService extends BaseSupportService
{
    /**
     * Returns integrated system version
     *
     * @return string
     */
    protected function getSystemVersion(): string
    {
        return InstalledVersions::getPrettyVersion('shopware/administration')
            . '@' . InstalledVersions::getReference('shopware/administration');
    }

    /**
     * Returns extension version
     *
     * @return string
     */
    protected function getIntegrationVersion(): string
    {
        $info = json_decode(file_get_contents(__DIR__ . '/../../../../composer.json'), true);

        return $info['version'];
    }

    /**
     * Returns dynamic content urls
     *
     * @return array
     */
    protected function getDynamicContentUrls(): array
    {
        $url = 'api.cleverreach.search.new';
        $params = [];
        if (version_compare($this->getParameterBag()->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $url = 'api.cleverreach.search';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return [
            $this->getUrlGenerator()->generate($url, $params, UrlGeneratorInterface::ABSOLUTE_URL)
        ];
    }

    /**
     * Returns webhook url
     *
     * @return array
     */
    protected function getWebhookUrl(): array
    {
        $formsWebhook = 'api.cleverreach.formsWebhook.new';
        $receiverWebhook = 'api.cleverreach.receiverWebhook.new';

        $params = [];
        if (version_compare($this->getParameterBag()->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $formsWebhook = 'api.cleverreach.formsWebhook';
            $receiverWebhook = 'api.cleverreach.receiverWebhook';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return [
            $this->getUrlGenerator()->generate($receiverWebhook, $params, UrlGeneratorInterface::ABSOLUTE_URL),
            $this->getUrlGenerator()->generate($formsWebhook, $params, UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }

    /**
     * Removes all data
     */
    protected function hardReset(): void
    {
        $this->getUninstallService()->removeData();
    }

    /**
     * @return UrlGeneratorInterface
     */
    private function getUrlGenerator(): UrlGeneratorInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UrlGeneratorInterface::class);
    }

    /**
     * @return UninstallService
     */
    private function getUninstallService(): UninstallService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UninstallService::class);
    }

    /**
     * @return ParameterBagInterface|object
     */
    private function getParameterBag(): ParameterBagInterface
    {
        return ServiceRegister::getService(ParameterBagInterface::class);
    }
}
