<?php

declare(strict_types=1);

namespace Sisi\Search\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Sisi\Search\Service\FilterService;
use Sisi\Search\ServicesInterfaces\InterfaceQuerylogSearchService;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\Events\SisiSearchPageLoadedEvent;
use Sisi\Search\Events\SisiSuggestPageLoadedEvent;
use Sisi\Search\Service\ExtSearchService;
use Sisi\Search\Service\MergeSeachQueryService;
use Sisi\Search\Service\ProductService;
use Sisi\Search\Service\RatingService;
use Sisi\Search\Service\SearchEventService;
use Sisi\Search\Service\SearchHelpService;
use Sisi\Search\Service\SearchService;
use Sisi\Search\Service\SortingService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Sisi\Search\ServicesInterfaces\InterfaceSisiProductPriceCalculator;
use Sisi\Search\Storefront\Page\SearchPageLoader;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchController extends StorefrontController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var searchService
     */
    protected $searchService;

    /**
     * @var InterfaceCreateCriteria
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
     * @var SearchPageLoader
     */
    private $loader;

    /**
     * @var InterSearchAjaxService
     */
    private $searchajax;


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
     * SearchController constructor.
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param InterfaceCreateCriteria $createCriteria
     * @param Logger $loggingService
     * @param InterfaceFrontendService $frontendService
     * @param SearchPageLoader $loader
     * @param EventDispatcherInterface $eventDispatcher
     * @param InterSearchAjaxService $searchajax
     * @param InterfaceSearchCategorieService $searchCategorieService
     * @param InterfaceQuerylogSearchService $querylogSearchService
     * @param InterfaceSisiProductPriceCalculator $sisiProductPriceCalculator ,
     * @param AbstractPropertyGroupSorter $propertyGroupSorter
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria,
        Logger $loggingService,
        InterfaceFrontendService $frontendService,
        SearchPageLoader $loader,
        EventDispatcherInterface $eventDispatcher,
        InterSearchAjaxService $searchajax,
        InterfaceSearchCategorieService $searchCategorieService,
        InterfaceQuerylogSearchService $querylogSearchService,
        InterfaceSisiProductPriceCalculator $sisiProductPriceCalculator,
        AbstractPropertyGroupSorter $propertyGroupSorter
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
        $this->loader = $loader;
        $this->eventDispatcher = $eventDispatcher;
        $this->searchajax = $searchajax;
        $this->sisiProductPriceCalculator = $sisiProductPriceCalculator;
        $this->propertyGroupSorter = $propertyGroupSorter;
    }

    /**
     * @HttpCache()
     * @Route("/onsuggest", name="frontend.search.onsuggest", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onSuggest(SalesChannelContext $context, Request $request)
    {
        $page = $this->loader->loadSuggest($request, $context);
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        $heandler = new SearchEventService($this->connection, $systemConfig, $this->container);
        $systemConfig['sisiProductPriceCalculator'] = $this->sisiProductPriceCalculator;
        $systemConfig['propertyGroupSorter'] = $this->propertyGroupSorter;
        $heandler->onSuggestSearch($page, $this->searchService, $this->frontendService, $context, $request, $systemConfig);
        $this->eventDispatcher->dispatch(
            new SisiSuggestPageLoadedEvent($page, $context, $request)
        );
        $pfad = "@Storefront/storefront/layout/header/search-suggest-es.html.twig";
        if (array_key_exists('themeES', $systemConfig)) {
            if (!empty($systemConfig['themeES'])) {
                $pfad = $systemConfig['themeES'];
            }
        }
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        return $this->renderStorefront(
            $pfad,
            ['page' => $page]
        );
    }

    /**
     * @HttpCache()
     * @Route("/onsearch", name="frontend.search.onsearch", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     *
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onSearch(SalesChannelContext $context, Request $request)
    {
        $page = $this->loader->load($request, $context);
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
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
            $context,
            $request,
            $systemConfig,
        );
        $this->eventDispatcher->dispatch(
            new SisiSearchPageLoadedEvent($page, $context, $request)
        );
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }

        return $this->renderStorefront('@Storefront/storefront/page/search/index.html.twig', ['page' => $page]);
    }

    /**
     * @HttpCache()
     * @Route("/onorder", name="frontend.search.onorder", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     *
     * @param SalesChannelContext $context
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onOrder(SalesChannelContext $context, Request $request)
    {
        $properties = $request->query->get('pro');
        $term = $request->query->get('search');
        $pageId = $request->query->get('p');
        $manufactoryIds = $request->query->get('ma');
        $rating = $request->query->get('ra');
        $price[0] = 0;
        $price[1] = 0;
        $price = $request->query->get('pri');
        $isReset = $request->query->get('rest');
        $elasticsearchAktive = true;
        $page = $this->loader->load($request, $context);
        $size = 10;
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        $hits = $systemConfig['producthitsSearch'];
        $criteria = new Criteria();
        $criteria->addAssociation('properties');
        $criteria->addAssociation('properties.group');
        $helpService = new SearchHelpService();
        $poductservice = new ProductService();
        $striphandler = new ExtSearchService();
        $heandlerrating = new RatingService();
        $hendlerHelpService = new SearchHelpService();
        $systemConfig['propertyGroupSorter'] = $this->propertyGroupSorter;
        $productService = $this->container->get('sales_channel.product.repository');
        $strRarting = true;
        if (array_key_exists('producthits', $systemConfig)) {
            $size = (int)$systemConfig['producthitsSearch'];
        }
        $from = $helpService->getFromvalue($size, $pageId);
        $getParams['from'] = $from;
        $getParams['size'] = $size;
        $newResultProp = [];
        $saleschannel = $context->getSalesChannel();
        $languageId = $saleschannel->getLanguageId();
        $getParams['pro'] = $properties;
        $getParams['ma'] = $manufactoryIds;
        $getParams['rating'] = $rating;
        $getParams['price'] = $price;
        $systemConfig['sisiProductPriceCalculator'] = $this->sisiProductPriceCalculator;
        if (empty($properties) && empty($manufactoryIds) && empty($rating) && empty($price)) {
            $newResult = $this->searchService->searchProducts(
                $term,
                $systemConfig,
                $pageId,
                $languageId,
                $saleschannel,
                $context,
                $this->frontendService,
                $this->container
            );
            if ($heandlerrating->weHaveRating($systemConfig)) {
                $strRarting = true;
            }
            if (array_key_exists('filterscrolling', $systemConfig)) {
                if ($systemConfig['filterscrolling'] === 'get') {
                    if ($newResult['hits']['total']['value'] == 1) {
                        $hendlerHelpService->redirectbyOne($this->container, $newResult);
                    }
                }
            }
        } else {
            $term = $striphandler->stripUrl($term, $systemConfig);
            $getParams['frontendService'] = $this->frontendService;
            $newResult = $this->searchajax->searchProducts(
                $term,
                $properties,
                $manufactoryIds,
                $systemConfig,
                $context,
                $this->connection,
                $getParams,
                $this->container
            );
        }
        $entities = null;
        $properties = null;
        $manufactories = null;
        if (!empty($newResult['hits']['hits'])) {
            $heandlerprice = new MergeSeachQueryService(
                $this->sisiProductPriceCalculator
            );
            $sortservice = new SortingService();
            $entities = $heandlerprice->selectedKindOfQueryResult($poductservice, $productService, $criteria, $newResult, $context, $systemConfig);
            $copyConfig = $systemConfig;
            if ($isReset !== "1") {
                $copyConfig['extraqueryforfilter'] = 'no';
            }
            $kindogpropteries = $sortservice->getKindofProperties($copyConfig);
            if ($kindogpropteries) {
                $getParams['from'] = 0;
                $getParams['size'] = $copyConfig['producthitsSearch'];
                $getParams['frontendService'] = $this->frontendService;
                $heandlerFilter = new FilterService();
                $newResultProp =  $heandlerFilter->getthequeryResultfortheFilter(
                    $term,
                    $getParams['pro'],
                    $manufactoryIds,
                    $systemConfig,
                    $context,
                    $this->connection,
                    $getParams,
                    $this->container,
                    $this->searchajax
                );
            }
            $propertiesAndmanufatory = $sortservice->getProperties($entities, $this->container, $copyConfig, $newResultProp, $languageId, $kindogpropteries);
            $properties = $propertiesAndmanufatory['properties'];
            $manufactories = $propertiesAndmanufatory['manufactories'];
        }
        $this->eventDispatcher->dispatch(
            new SisiSearchPageLoadedEvent($page, $context, $request)
        );
        if ($page->getMetaInformation()) {
            $page->getMetaInformation()->setRobots('noindex,follow');
        }
        $page->assign(
            [
                'sisi_elasticsearchResults' => $entities,
                'sisi_properties' => $properties,
                'sisi_manufactories' => $manufactories,
                'sisi_elasticsearchAktive' => $elasticsearchAktive,
                'sisi_rating' =>  $strRarting,
                'sisi_search_hits' => $hits,
                'ESorginalResult' => $newResult,
                'pageindex' => $pageId,
                'cre' => $criteria,
                'sisi_sytemconfig' => $systemConfig
            ]
        );
        return $this->renderStorefront('@Storefront/storefront/page/search/index.html.twig', ['page' => $page]);
    }
}
