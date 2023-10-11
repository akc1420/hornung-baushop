<?php

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Shopware\Core\Content\ProductExport\Validator\XmlValidator;
use Shopware\Core\Framework\Adapter\Cache\CacheIdLoader;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\Security\Api\ConfigController;
use Swag\Security\Api\SecurityController;
use Swag\Security\Components\State;
use Swag\Security\Fixes\NEXT10905\XmlValidatorDecorator;
use Swag\Security\Fixes\NEXT14482\ApiCriteriaValidator;
use Swag\Security\Fixes\NEXT14533\PaymentService;
use Swag\Security\Subscriber\AdminSecurityFixesProvider;
use Swag\Security\Subscriber\TwigStateProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(State::class)
            ->args([
                '%SwagPlatformSecurity.availableFixes%',
                '%SwagPlatformSecurity.activeFixes%'
            ])
        ->set(SecurityController::class)
            ->public()
            ->args([
                new Reference(State::class),
                new Reference('plugin.repository'),
                '%kernel.cache_dir%',
                new Definition(Client::class),
                new Reference(CacheIdLoader::class),
            ])
        ->set(ConfigController::class)
            ->public()
            ->args([
                new Reference(Connection::class),
                new Reference('user.repository'),
            ])
        ->set(AdminSecurityFixesProvider::class)
            ->public()
            ->args([
                new Reference(State::class),
            ])
            ->tag('kernel.event_listener')
        ->set(TwigStateProvider::class)
            ->public()
            ->args([
                new Reference(State::class),
            ])
            ->tag('kernel.event_listener')
        ->set(XmlValidatorDecorator::class)
            ->public()
            ->decorate(XmlValidator::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([new Reference(XmlValidatorDecorator::class . '.inner')])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT10905\SecurityFix::class])
        ->set(ApiCriteriaValidator::class)
            ->public()
            ->decorate(RequestCriteriaBuilder::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([new Reference(ApiCriteriaValidator::class . '.inner')])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14482\SecurityFix::class])
        ->set(PaymentService::class)
            ->public()
            ->decorate(\Shopware\Core\Checkout\Payment\PaymentService::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([
                new Reference('order.repository'),
                new Reference(SalesChannelContextService::class),
                new Reference(SalesChannelContextPersister::class),
                new Reference(PaymentService::class . '.inner')
            ])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14533\SecurityFix::class])
    ;

    // Fixes
    $container->services()
        ->set(Swag\Security\Fixes\NEXT9241\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9241\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT9240\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9240\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT9242\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9242\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT9243\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9243\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT9569\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9569\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT10909\SecurityFix::class)
            ->args(['%kernel.environment%'])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT10909\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT10905\SecurityFix::class)
            ->args(['%kernel.environment%'])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT10905\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT12230\SecurityFix::class)
            ->args(['%kernel.environment%'])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT12230\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT9689\SecurityFix::class)
            ->args(['%kernel.environment%'])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT9689\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT12359\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT12359\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT13371\SecurityFix::class)
            ->args([new Reference(\Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry::class)])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT13371\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT13247\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT13247\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT12824\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT12824\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT13664\SecurityFix::class)
            ->args([new Reference(\Symfony\Component\HttpFoundation\RequestStack::class)])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT13664\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT13896\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT13896\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT14482\SecurityFix::class)
            ->args(['%kernel.environment%'])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14482\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT14533\SecurityFix::class)
            ->args([
                new Reference(SalesChannelContextService::class),
                new Reference('order.repository'),
                new Reference(SalesChannelContextPersister::class)
            ])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14533\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT15183\SecurityFix::class)
            ->args([
                new Reference('order.repository'),
            ])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT15183\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT14744\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14744\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT14871\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14871\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT14883\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT14883\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT15669\SecurityFix::class)
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT15669\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT15673\SecurityFix::class)
            ->decorate(\Shopware\Core\Content\Media\File\FileUrlValidatorInterface::class, null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([new Reference(Swag\Security\Fixes\NEXT15673\SecurityFix::class . '.inner')])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT15673\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT15681\SecurityFix::class)
            ->args([
                new Reference('product_review.repository'),
            ])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT15681\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT16429\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT16429\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT16429\ProductSalesChannelRepositoryDecorator::class)
            ->decorate('sales_channel.product.repository', null, 0, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
            ->args([
                new Reference(Swag\Security\Fixes\NEXT16429\ProductSalesChannelRepositoryDecorator::class . '.inner'),
            ])
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT16429\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT15675\SecurityFix::class)
            ->args([
                new Reference('import_export_file.repository'),
                new Reference('event_dispatcher'),
            ])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT15675\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT15675\PrepareDownloadController::class)
            ->public(true)
            ->args([
                new Reference('import_export_file.repository'),
                new Reference(State::class),
            ])
        ->set(\Swag\Security\Fixes\NEXT17527\RequestTransformerFixer::class)
            ->public(true)
            ->decorate(\Shopware\Core\Framework\Routing\RequestTransformerInterface::class)
            ->args([new Reference(\Swag\Security\Fixes\NEXT17527\RequestTransformerFixer::class . '.inner')])
             ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT17527\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT19276\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT19276\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT19820\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT19820\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT20309\SecurityFix::class)
            ->args([new Reference('service_container')])
            ->tag('kernel.event_subscriber')
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT20309\SecurityFix::class])
        ->set(Swag\Security\Fixes\NEXT20348\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => Swag\Security\Fixes\NEXT20348\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT20305\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT20305\SecurityFix::class])
            ->tag('kernel.event_subscriber')
        ->set(\Swag\Security\Fixes\NEXT21078\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT21078\SecurityFix::class])
            ->tag('kernel.event_subscriber')
        ->set(\Swag\Security\Fixes\NEXT21034\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT21034\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT23325\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT23325\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT23464\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT23464\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT23562\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT23562\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT22891\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT22891\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT24679\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT24679\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT22891\NewsletterSubscribeRouteDecorator::class)
            ->decorate(\Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute::class)
            ->args([
                new Reference(\Swag\Security\Fixes\NEXT22891\NewsletterSubscribeRouteDecorator::class . '.inner'),
                new Reference(SystemConfigService::class),
            ])
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT22891\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT24679\DoctrineSQLHandlerDecorator::class)
            ->decorate(\Shopware\Core\Framework\Log\Monolog\DoctrineSQLHandler::class)
            ->args([
                new Reference(Connection::class),
            ])
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT24679\SecurityFix::class])
        ->set(\Swag\Security\Fixes\NEXT26140\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT26140\SecurityFix::class])
        ->set(\Swag\Security\Fixes\PPI737\SecurityFix::class)
            ->tag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\PPI737\SecurityFix::class])

    ;
};
