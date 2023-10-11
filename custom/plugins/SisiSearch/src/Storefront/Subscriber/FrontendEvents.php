<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use _HumbugBox3ab8cff0fda0\VARIANT;
use phpDocumentor\Reflection\Types\Boolean;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Sisi\Search\Service\FrontendService;
use Sisi\Search\Service\SearchEventService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sisi\Search\Components\CategoryService;
use Sisi\Search\Components\ManufactoryService;
use Sisi\Search\Service\SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\ESindexing\CreateCriteria;
use Doctrine\DBAL\Connection;
use Sisi\Search\Service\ProductService;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Sisi\Search\Service\SortingService;
use Shopware\Core\Content\Product\SalesChannel;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class FrontendEvents implements EventSubscriberInterface
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @param SystemConfigService $systemConfigService
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService
    ) {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * {@inheritDoc}
     */

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onFrontend'
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param StorefrontRenderEvent $event
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *  @SuppressWarnings(PHPMD.NPathComplexity)
     */

    public function onFrontend(StorefrontRenderEvent $event): void
    {
        $saleschannelContext = $event->getSalesChannelContext();
        $systemConfig = $this->systemConfigService->get(
            "SisiSearch.config",
            $saleschannelContext->getSalesChannel()->getId()
        );
        $context = $event->getContext();
        if ($context !== null) {
            $languageId = $context->getLanguageId();
            $event->setParameter('sisilanguageId', $languageId);
        }
        if (array_key_exists('ajaxcontroller', $systemConfig)) {
            $event->setParameter('ajaxcontroller', $systemConfig['ajaxcontroller']);
        }
        if (array_key_exists('searchcontroller', $systemConfig)) {
            $event->setParameter('searchcontroller', $systemConfig['searchcontroller']);
        }
        if (array_key_exists('disabableAjax', $systemConfig)) {
            $event->setParameter('disabableAjax', $systemConfig['disabableAjax']);
        }
        if (array_key_exists('form', $systemConfig)) {
            $event->setParameter('form', $systemConfig['form']);
        }
        if (array_key_exists('filterscrolling', $systemConfig)) {
            $event->setParameter('filterscrolling', $systemConfig['filterscrolling']);
        }
        if (array_key_exists('querylog', $systemConfig)) {
            if ($systemConfig['querylog'] === '1') {
                $event->setParameter('sisiquerylog', $systemConfig['querylog']);
            }
        }
        if (array_key_exists('filterscrollingPopup', $systemConfig)) {
            if ($systemConfig['filterscrollingPopup'] === 'yes') {
                $event->setParameter('filterscrollingPopup', 'yes');
            }
        }
        $reuquestUri = $event->getRequest()->getRequestUri();
        $geturi = $event->getRequest()->query->get('search');
        $url = "//" . $event->getRequest()->getHttpHost();
        if (array_key_exists("search", $_GET)) {
            $url = $url . $reuquestUri . DIRECTORY_SEPARATOR . "?search=" . $geturi;
        }
        $event->setParameter('sisiurl', $url);
    }
}
