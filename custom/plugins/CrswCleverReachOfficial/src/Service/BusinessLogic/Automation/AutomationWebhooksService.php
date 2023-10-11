<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\AutomationWebhooksService
    as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AutomationWebhooksService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation
 */
class AutomationWebhooksService implements BaseService
{
    /**
     * Provides automation webhook url.
     *
     * @param $automationId
     *
     * @return string
     */
    public function getWebhookUrl($automationId): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);
        $routeName = 'api.cleverreach.automationWebhook.new';
        $params = ['crAutomationId' => $automationId];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.cleverreach.automationWebhook';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()
            ->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);

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
