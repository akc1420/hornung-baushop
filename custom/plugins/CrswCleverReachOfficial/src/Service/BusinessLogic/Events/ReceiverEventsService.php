<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Events;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService as BaseReceiverEventsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ReceiverEventsService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Events
 */
class ReceiverEventsService extends BaseReceiverEventsService
{
    /**
     * Provides url that will listen to web hook requests.
     *
     * @return string
     */
    public function getEventUrl(): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);

        $receiverWebhook = 'api.cleverreach.receiverWebhook.new';
        $params = [];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $receiverWebhook = 'api.cleverreach.receiverWebhook';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()
            ->generate($receiverWebhook, $params, UrlGeneratorInterface::ABSOLUTE_URL);
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