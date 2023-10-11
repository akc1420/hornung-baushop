<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Events;


use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService as BaseFormEventsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FormEventsService extends BaseFormEventsService
{
    /**
     * Provides url that will listen to web hook requests.
     *
     * @return string
     */
    public function getEventUrl(): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);

        $formsWebhook = 'api.cleverreach.formsWebhook.new';
        $params = [];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $formsWebhook = 'api.cleverreach.formsWebhook';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()
            ->generate($formsWebhook, $params, UrlGeneratorInterface::ABSOLUTE_URL);
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