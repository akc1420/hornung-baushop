<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPage;
use Sisi\Search\Components\CategoryService;
use Sisi\Search\Components\ManufactoryService;
use Sisi\Search\ESindexing\CreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterSearchAjaxService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;
use Symfony\Bridge\Monolog\Logger;
use Sisi\Search\Service\CriteriaService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Page\Search\SearchPage;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Sisi\Search\Service\SearchHelpService;

/**
 * Class SearchEventService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD)
 */
class SearchEventService
{
    /**
     * @var Connection
     */
    private $connection;


    /**
     * @var array
     */
    protected $systemConfig;


    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * SearchEventService constructor.
     * @param Connection $connection
     * @param array $systemConfig
     * @param ContainerInterface $container )
     */
    public function __construct(Connection $connection, array $systemConfig, ContainerInterface $container)
    {
        $this->connection = $connection;
        $this->systemConfig = $systemConfig;
        $this->container = $container;
    }

    /**
     * @param SuggestPage $page
     * @param SearchService $searchService
     * @param InterfaceFrontendService $frontendService
     * @param SalesChannelContext $saleschannelContext
     * @param Request $request
     */
    public function onSuggestSearch(
        SuggestPage $page,
        SearchService $searchService,
        InterfaceFrontendService $frontendService,
        SalesChannelContext $saleschannelContext,
        Request $request,
        array $config
    ): void {
        $strwith = true;
        $categorietree = [];
        $merkercat = [];
        $merkerManu = [];
        $manufacturerIds = [];
        $newResult['producte'] = [];
        $newResult['manufacturer'] = [];
        $newResult['categories'] = [];
        $newResult['manufatory'] = [];
        $newResult['querylogs'] = [];
        $newResult['sortedProperties'] = [];
        $indexMa = 0;
        $service = new CategoryService();
        $manufactoryService = new ManufactoryService();
        $heandlerprice = new MergeSeachQueryService(
            $config['sisiProductPriceCalculator']
        );
        $elasticsearchAktive = false;
        $term = $page->getSearchTerm();
        $pro = $request->query->get('pro');
        $thunbnailWithHeight = [];
        $sideBar['categorietree'] = [];
        $sideBar['manufacturerIds'] = [];
        $newResult['size'] = 10;
        $strWeHaveHost = false;
        $terms['product'] = $term;
        $terms['ma'] = $request->query->get('ma');
        $terms['from'] = $request->query->get('p');
        $terms['pro'] = $pro;
        $terms['cat'] = $request->query->get('cat');
        if (array_key_exists('thumbnailsID', $config)) {
            $thunbnailWithHeight = explode('x', trim($config['thumbnailsID']));
        }
        if (array_key_exists('elasticsearchAktive', $config)) {
            if ($config['elasticsearchAktive'] == '1') {
                $elasticsearchAktive = true;
            }
        }
        if (array_key_exists('host', $config)) {
            $strWeHaveHost = true;
        }
        if ($elasticsearchAktive && $strWeHaveHost) {
            $newResult = $searchService->search(
                $terms,
                $config,
                $saleschannelContext,
                $frontendService,
                $this->container
            );
        } else {
            $searchResult = $page->getSearchResult();
            $newResult['categorien'] = [];
            $sideBar = $this->getAllVariablesWithOutES(
                $searchResult,
                $merkercat,
                $service,
                $merkerManu,
                $manufactoryService,
                $indexMa,
                $categorietree,
                $manufacturerIds
            );
            $strwith = $this->getWith($config, $categorietree, $manufacturerIds);
        }
        if ($newResult['producte'] == 'noindex') {
            return;
        }
        $elasticsearchResults = $frontendService->ownFilter($newResult['producte']);
        $propForTheme = 0;
        if ($pro !== null) {
            if (is_array($pro)) {
                $propForTheme = end($pro);
            }
        }
        if (!empty($config["hideMainCategory"])) {
            $value = $config["hideMainCategory"];
            $chunks = array_filter(explode("\n", $value));
            if (!empty($newResult["categorien"])) {
                foreach ($newResult["categorien"]["hits"]["hits"] as $key => $value) {
                    $value = $value["_source"]["category_breadcrumb"];
                    if (empty($value)) {
                        continue;
                    }
                    $replaced_values_category = array_values(array_filter(explode("/", $value)));
                    foreach ($chunks as $chunk) {
                        if (strpos($replaced_values_category[0], $chunk) !== false) {
                            array_shift($replaced_values_category);
                            $joinedString = implode('/', $replaced_values_category);
                            $newResult["categorien"]["hits"]["hits"][$key]["_source"]["category_breadcrumb"] = "/" . $joinedString;
                            continue;
                        }
                    }
                }
            }
        }


        $page->assign(
            [
                'thunbnail_with_height' => $thunbnailWithHeight,
                'sisi_col_search' => $config,
                'categorietree' => $sideBar['categorietree'],
                'manufactures' => $sideBar['manufacturerIds'],
                'strwith' => $strwith,
                'sisi_elasticsearchAktive' => $elasticsearchAktive,
                'sisi_elasticsearchResults' => $elasticsearchResults,
                'sisi_manufacturer' => $newResult['manufatory'],
                'sisi_categorien' => $newResult['categorien'],
                'sisi_sortedProperties' => $newResult['sortedProperties'],
                'sisi_size' => $newResult['size'],
                'sisi_from' => ($terms['from'] == null ? 0 : $terms['from']),
                'sisi_ma' => $terms['ma'],
                'sisi_cat' => $terms['cat'],
                'sisi_pro' => $propForTheme,
                'sisi_querylogs' => $newResult['querylogs']
            ]
        );
    }

    /**
     * @param SearchPage $page
     * @param InterfaceCreateCriteria $createCriteria
     * @param ContainerInterface $container
     * @param SearchService $searchService
     * @param InterfaceFrontendService $frontendService
     * @param SalesChannelContext $context
     * @param Request $request
     */
    public function onSearch(
        SearchPage $page,
        InterfaceCreateCriteria $createCriteria,
        ContainerInterface $container,
        SearchService $searchService,
        InterSearchAjaxService $searchajax,
        InterfaceFrontendService $frontendService,
        SalesChannelContext $context,
        Request $request,
        array $config
    ): bool {
        if (array_key_exists('elasticsearchAktive', $config)) {
            if ($config['elasticsearchAktive'] == '1') {
                $elasticsearchAktive = true;
                $saleschannel = $context->getSalesChannel();
                $languageId = $saleschannel->getLanguageId();
                $term = trim($page->getSearchTerm());
                $manufactory = $request->query->get('ma');
                $getParams['price'] = $request->query->get('pri');
                $getParams['rating'] = $request->query->get('ra');
                $criteria = new Criteria();
                $poductservice = new ProductService();
                $hendlerHelpService = new SearchHelpService();
                $entities = null;
                $newResult = [];
                $properties = [];
                $manufactories = [];
                $newResultProp = [];
                $pageId = $request->query->get('p');
                $heandlerprice = new MergeSeachQueryService(
                    $config['sisiProductPriceCalculator']
                );
                if ($pageId == null) {
                    $pageId = 0;
                }
                $hits = 10;
                if (array_key_exists('producthitsSearch', $config)) {
                    $hits = $config['producthitsSearch'];
                }
                $getParams['size'] = $hits;
                $createCriteria->getCriteria($criteria);
                $productService = $container->get('sales_channel.product.repository');
                if (!empty($manufactory)) {
                    $criteria = $poductservice->searchProdukteManufactory($criteria, $term);
                    $entities = $productService->search($criteria, $context);
                    $newResult['hits']['total']["value"] = $entities->getTotal();
                    $poductservice->setOffsetandLimit($criteria, $hits, (int)$pageId);
                } else {
                    $newResult = $searchService->searchProducts(
                        $term,
                        $config,
                        $pageId,
                        $languageId,
                        $saleschannel,
                        $context,
                        $frontendService,
                        $this->container
                    );
                    $newResult = $frontendService->ownFilter($newResult);
                    if (count($newResult) > 0) {
                        if (count($newResult['hits']['hits']) > 0) {
                            $entities = $heandlerprice->selectedKindOfQueryResult(
                                $poductservice,
                                $productService,
                                $criteria,
                                $newResult,
                                $context,
                                $config
                            );
                            $sortservice = new SortingService();
                            $kindogpropteries = $sortservice->getKindofProperties($config);
                            if ($kindogpropteries) {
                                $getParams['from'] = 0;
                                $getParams['frontendService'] = $frontendService;
                                $getParams['size'] = $config['producthitsSearch'];
                                $heandlerFilter = new FilterService();
                                $newResultProp = $heandlerFilter->getthequeryResultfortheFilter(
                                    $term,
                                    $request->query->get('pro'),
                                    $manufactory,
                                    $config,
                                    $context,
                                    $this->connection,
                                    $getParams,
                                    $this->container,
                                    $searchajax
                                );
                            }
                            $propertiesAndmanufatory = $sortservice->getProperties(
                                $entities,
                                $this->container,
                                $config,
                                $newResultProp,
                                $languageId,
                                $kindogpropteries
                            );
                            $properties = $propertiesAndmanufatory['properties'];
                            $manufactories = $propertiesAndmanufatory['manufactories'];
                        }
                        if ($newResult['hits']['total']['value'] == 1) {
                            $hendlerHelpService->redirectbyOne($this->container, $newResult);
                        }
                    } else {
                        $config['elasticsearchAktive'] = 2;
                    }
                }

                $page->assign(
                    [
                        'sisi_elasticsearchResults' => $entities,
                        'sisi_sytemconfig' => $config,
                        'sisi_properties' => $properties,
                        'sisi_manufactories' => $manufactories,
                        'sisi_rating' => true,
                        'sisi_elasticsearchAktive' => $elasticsearchAktive,
                        'sisi_search_hits' => $hits,
                        'ESorginalResult' => $newResult,
                        'pageindex' => $pageId
                    ]
                );

                return true;
            }
        }
        $page->assign(
            ['sisi_sytemconfig' => $config]
        );
        return true;
    }


    /**
     * @param ProductListingResult|EntitySearchResult<ProductEntity> $searchResult
     * @param array $merkercat
     * @param CategoryService $service
     * @param array $merkerManu
     * @param ManufactoryService $manufactoryService
     * @param int $indexMa
     * @param array $categorietree
     * @param array $manufacturerIds
     * @return array[]
     */
    private function getAllVariablesWithOutES(
        &$searchResult,
        array $merkercat,
        CategoryService $service,
        array $merkerManu,
        ManufactoryService $manufactoryService,
        int $indexMa,
        array &$categorietree,
        array &$manufacturerIds
    ): array {
        /** @var ProductEntity $produkt */
        foreach ($searchResult as $produkt) {
            $treeItems = $produkt->getCategoryTree();
            foreach ($treeItems as $key => $treeItem) {
                if (!in_array($treeItem, $merkercat)) {
                    $categorieName = $service->getCategorieNameById($this->connection, $treeItem);
                    $categorietree[$key] = ['id' => $treeItem, 'name' => $categorieName];
                    $merkercat[] = $treeItem;
                }
            }
            $manufacturerId = $produkt->getManufacturerId();
            if ($manufacturerId != null && !in_array($manufacturerId, $merkerManu)) {
                $name = $manufactoryService->getManufactoryById($this->connection, $manufacturerId);
                $manufacturerIds[$indexMa] = ['id' => $manufacturerId, 'name' => $name];
                $merkerManu[] = $manufacturerId;
                $indexMa++;
            }
        }
        return ['categorietree' => $categorietree, 'manufacturerIds' => $manufacturerIds];
    }

    private function getWith(array $systemConfig, array &$categorietree, array &$manufacturerIds): bool
    {
        $strwith = true;
        if (array_key_exists('categorien', $systemConfig) && array_key_exists('manufacturer', $systemConfig)) {
            if ($systemConfig['categorien'] == '2' && $systemConfig['manufacturer'] == '2') {
                $strwith = false;
            }
        }
        if (count($categorietree) == 0 && count($manufacturerIds) == 0) {
            $strwith = false;
        }
        return $strwith;
    }
}
