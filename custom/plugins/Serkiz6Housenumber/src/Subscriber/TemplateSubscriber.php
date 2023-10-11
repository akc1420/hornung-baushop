<?php declare(strict_types=1);

namespace Serkiz6Housenumber\Subscriber;

use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;

use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoadedEvent;


use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;

use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;

use Shopware\Core\System\SystemConfig\SystemConfigService;

use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;


use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;

class TemplateSubscriber implements EventSubscriberInterface
{

    const CONFIG_NAMESPACE = 'Serkiz6Housenumber';

    private $activatedCountries = null;

    private $container;
    private $context;
    private $systemConfigService;
  
    public function __construct($container,$systemConfigService)
    {
      $this->container = $container;
      $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HeaderPageletLoadedEvent::class => 'onHeaderPageletLoaded'
        ];
    }

    /**
     * @param AccountPaymentMethodPageLoadedEvent|HeaderPageletLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    public function onHeaderPageletLoaded($event): void
    {
        try {

            if($event instanceof HeaderPageletLoadedEvent){
                $pluginConfig = $this->systemConfigService->getDomain(self::CONFIG_NAMESPACE);
                if($pluginConfig){
                    $context = $event->getSalesChannelContext();
                    $struct = new SerkizConfigStruct($pluginConfig,$context);
                    $event->getPagelet()->addExtension(self::CONFIG_NAMESPACE, $struct);
                }
            }

        }catch (\Exception $e) {
            return;
        }
    }

}