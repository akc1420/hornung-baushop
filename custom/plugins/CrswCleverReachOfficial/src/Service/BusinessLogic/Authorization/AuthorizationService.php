<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Authorization;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\AuthorizationService as BaseAuthorizationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthorizationService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Authorization
 */
class AuthorizationService extends BaseAuthorizationService
{
    /**
     * Retrieves authorization redirect url.
     *
     * @param bool $isRefresh Specifies whether url is retrieved for token refresh.
     *
     * @return string Authorization redirect url.
     */
    public function getRedirectURL($isRefresh = false): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);
        $routeName = 'api.cleverreach.auth.new';
        $params = ['isRefresh' => $isRefresh];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.cleverreach.auth';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()
            ->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Retrieves color code of authentication iframe background.
     *
     * @return string Color code.
     */
    public function getAuthIframeColor(): string
    {
        return 'f4f6f8';
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