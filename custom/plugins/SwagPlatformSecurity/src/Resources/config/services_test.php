<?php

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Shopware\Core\Content\ProductExport\Validator\XmlValidator;
use Shopware\Core\Framework\Adapter\Cache\CacheIdLoader;
use Swag\Security\Api\ConfigController;
use Swag\Security\Api\SecurityController;
use Swag\Security\Components\State;
use Swag\Security\Fixes\NEXT10905\XmlValidatorDecorator;
use Swag\Security\Subscriber\AdminSecurityFixesProvider;
use Swag\Security\Subscriber\TwigStateProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(\Swag\Security\Fixes\NEXT9689\MediaFileFetchRedirectTestController::class)
        ->public();
};
