<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\Service\SearchEventService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Sisi\Search\ServicesInterfaces\InterfaceSisiProductPriceCalculator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sisi\Search\Service\SearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchEvents implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @var Connection
     */
    private $connection;


    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     * @var searchService
     */
    private $searchService;


    /**
     * @var  InterfaceCreateCriteria
     */
    private $createCriteria;


    /**
     *
     * @var Logger
     */
    protected $loggingService;


    /**
     *
     * @var InterfaceFrontendService
     */
    private $frontendService;

    /**
     * @var InterfaceSearchCategorieService
     */
    protected $searchCategorieService;


    /**
     * @var EntityRepositoryInterface
     *
     */
    protected EntityRepositoryInterface $unitRepository;

    /**
     * @var QuantityPriceCalculator
     *
     */
    protected QuantityPriceCalculator $calculator;

    /**
     * @var AbstractPropertyGroupSorter
     */
    protected $propertyGroupSorter;


    /**
     * @var  InterfaceSisiProductPriceCalculator
     */
    protected $sisiProductPriceCalculator;

    /**
     * @var  InterSearchAjaxService
     */
    private $searchajax;


    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceCreateCriteria $createCriteria
     * @param Logger $loggingService
     * @param InterfaceFrontendService $frontendService
     * @param InterfaceSearchCategorieService $searchCategorieService
     * @param InterfaceSisiProductPriceCalculator $sisiProductPriceCalculator
     * @param InterfaceQuerylogSearchService $querylogSearchService ,
     * @param AbstractPropertyGroupSorter $propertyGroupSorter
     * @param InterSearchAjaxService $searchajax
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria,
        Logger $loggingService,
        InterfaceFrontendService $frontendService,
        InterfaceSearchCategorieService $searchCategorieService,
        InterfaceSisiProductPriceCalculator $sisiProductPriceCalculator,
        InterfaceQuerylogSearchService $querylogSearchService,
        AbstractPropertyGroupSorter $propertyGroupSorter,
        InterSearchAjaxService $searchajax
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->container = $container;
        $this->loggingService = $loggingService;
        $this->frontendService = $frontendService;
        $this->searchCategorieService = $searchCategorieService;
        $this->searchService = new SearchService(
            $systemConfigService,
            $connection,
            $container,
            $loggingService,
            $searchCategorieService,
            $querylogSearchService
        );
        $this->createCriteria = $createCriteria;
        $this->sisiProductPriceCalculator = $sisiProductPriceCalculator;
        $this->propertyGroupSorter = $propertyGroupSorter;
        $this->searchajax = $searchajax;
    }

    /**
     * {@inheritDoc}
     */

    public static function getSubscribedEvents(): array
    {
        return [
            SuggestPageLoadedEvent::class => 'onSuggestSearch',
            SearchPageLoadedEvent::class => 'onSearch'
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SearchPageLoadedEvent $event
     */
    public function onSearch(SearchPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $saleschannelContext = $event->getSalesChannelContext();
        $request = $event->getRequest();
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $saleschannelContext->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $systemConfig['sisiProductPriceCalculator'] = $this->sisiProductPriceCalculator;
        $systemConfig['propertyGroupSorter'] = $this->propertyGroupSorter;
        $heandler->onSearch(
            $page,
            $this->createCriteria,
            $this->container,
            $this->searchService,
            $this->searchajax,
            $this->frontendService,
            $saleschannelContext,
            $request,
            $systemConfig,
        );
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param SuggestPageLoadedEvent $event
     */
    public function onSuggestSearch(SuggestPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $saleschannelContext = $event->getSalesChannelContext();
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $saleschannelContext->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $request = $event->getRequest();
        $systemConfig['sisiProductPriceCalculator'] = $this->sisiProductPriceCalculator;
        $systemConfig['propertyGroupSorter'] = $this->propertyGroupSorter;
        $heandler->onSuggestSearch($page, $this->searchService, $this->frontendService, $saleschannelContext, $request, $systemConfig);
    }
}
