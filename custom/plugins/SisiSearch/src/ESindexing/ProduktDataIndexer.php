<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertProduktDataIndex;
use Sisi\Search\ESIndexInterfaces\InterfaceProduktDataMapping;
use Sisi\Search\ESIndexInterfaces\InterfaceProduktDataSettings;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\MultiLanuageServiceService;
use Sisi\Search\Service\SearchkeyService;
use Sisi\Search\Service\TextService;
use Sisi\Search\Service\VariantenService;
use Symfony\Bridge\Monolog\Logger;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\IndexService;
use Sisi\Search\Service\InsertTimestampService;
use Sisi\Search\Service\ProductService;
use Sisi\Search\Service\StepService;
use Sisi\Search\Service\TranslationService;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Symfony\Component\ErrorHandler\Debug;

/**
 * Class ProduktDataIndexer
 * @package Sisi\Search\ESindexing
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProduktDataIndexer implements AbstractDataIndexer
{
    /**
     * @var InterfaceProduktDataSettings
     */
    protected $produktDataSettings;

    /**
     * @var InterfaceProduktDataMapping
     */
    protected $produktDataMapping;

    /**
     * @var CreateIndex
     */
    protected $createindex;

    /**
     * @var InterfaceCreateCriteria
     */
    protected $createCriteria;

    /**
     * @var InterfaceInsertProduktDataIndex
     */
    protected $inserProduktDataIndex;


    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;


    /**
     * ProduktDataIndexer constructor.
     * @param InterfaceProduktDataSettings $produktDataSettings
     * @param InterfaceProduktDataMapping $produktDataMapping
     * @param CreateIndex $createindex
     * @param InterfaceCreateCriteria $createCriteria
     * @param InterfaceInsertProduktDataIndex $inserProduktDataIndex
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        InterfaceProduktDataSettings $produktDataSettings,
        InterfaceProduktDataMapping $produktDataMapping,
        CreateIndex $createindex,
        InterfaceCreateCriteria $createCriteria,
        InterfaceInsertProduktDataIndex $inserProduktDataIndex,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->produktDataSettings = $produktDataSettings;
        $this->produktDataMapping = $produktDataMapping;
        $this->createindex = $createindex;
        $this->createCriteria = $createCriteria;
        $this->inserProduktDataIndex = $inserProduktDataIndex;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param SystemConfigService $config
     * @param QuantityPriceCalculator $priceCalculator
     * @param AbstractSalesChannelContextFactory $salesChannelContextFactory
     * @param Logger $loggingService
     * @param array $parameters
     * @param OutputInterface|null $output
     * @return array
     * @throws \Doctrine\DBAL\Exception
     *
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function poppulate(
        $connection,
        $container,
        $config,
        $priceCalculator,
        $salesChannelContextFactory,
        $loggingService,
        $parameters,
        $output
    ): array {
        $total = 0;
        $usetime = 0;
        if ($output !== null) {
            Debug::enable();
        }
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $criteriaHandler = new CriteriaService();
        $stepHaendler = new StepService();
        $timeHaendler = new InsertTimestampService();
        $indexHaendler = new IndexService();
        $transHaendler = new TranslationService();
        $heandlerSynms = new SearchkeyService();
        $texthaendler = new TextService();
        $haendlerVariants = new VariantenService();
        $heandlerMultilanuage = new MultiLanuageServiceService();
        $criteriavaraintsFields = [];
        $saleschannel = $container->get('sales_channel.repository');
        $criteriaChannel = new Criteria();
        $shop = "";
        $fieldsconfig = "";
        $lanugageId = "";
        if (array_key_exists('shop', $parameters)) {
            $shop = $parameters['shop'];
            // string manipulation extract channel
            $shop = str_replace("shop=", "", $shop);
        }
        if (array_key_exists('shopID', $parameters)) {
            $shop = "shopID=" . $parameters['shopID'];
        }
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->getElements();
        $channelId = $this->getArrayFirst($salechannelItem);
        $listingSettings = $config->get("core.listing");
        $config = $config->get("SisiSearch.config", $channelId);
        $heandlerClient = new ClientService();
        $client = $heandlerClient->createClient($config);
        $productService = $container->get('sales_channel.product.repository');
        $sisiProductService = new ProductService();
        $time = time();
        $strChannel = $indexHaendler->checkshop($shop, $loggingService);
        $strChannel2 = $indexHaendler->checkChannelId($salechannelItem, $loggingService);
        $salechannelItem = array_shift($salechannelItem);
        $parameters['channelId'] = $channelId;
        $parameters['categorie_id'] = $salechannelItem->getNavigationCategoryId();
        $lanugageValues = $transHaendler->getLanguageId($parameters, $connection, $output, $loggingService);
        $variantsFields = [];
        $allLangugesIds = $heandlerMultilanuage->getAllChannelLanguages($parameters['channelId'], $connection);
        $parameters['isAll'] = 'no';
        $token = $contextService->getRandomHex();
        if ($lanugageValues === 'all') {
            $lanuagesArray = [];
            $parameters['isAll'] = 'all';
            $languageIdSelektor = [SalesChannelContextService::LANGUAGE_ID];
            foreach ($allLangugesIds as $allLangugesId) {
                $lanuagesArray[] = $allLangugesId["HEX(langtable.language_id)"];
            }
        } else {
            $lanugageId = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem, $parameters);
            $lanuagesArray[] = $lanugageId;
        }
        if ($strChannel && $strChannel2) {
            foreach ($lanuagesArray as $allLangugesId) {
                if ($lanugageValues !== 'all') {
                    $languageIdSelektor = [SalesChannelContextService::LANGUAGE_ID => strtolower($allLangugesId)];
                } else {
                    $languageIdSelektor = [];
                }
                if (array_key_exists('configLanguage', $config)) {
                    if (!empty($config['configLanguage'])) {
                        $languageIdSelektor = [SalesChannelContextService::LANGUAGE_ID => strtolower($config['configLanguage'])];
                    }
                }
                $saleschannelContext = $salesChannelContextFactory->create(
                    $token,
                    $channelId,
                    $languageIdSelektor
                );
                $parameters['languageID'] = $allLangugesId;
                $parameters['language_id'] = $allLangugesId;
                $lanugageId = $allLangugesId;
                $lanuageName = $indexHaendler->getLanuageNameById($connection, $lanugageId);
                $parameters['language'] = $lanuageName;
                $parameters['esIndex'][$allLangugesId] = $timeHaendler->getTheESIndex(
                    $time,
                    $parameters,
                    $connection,
                    $channelId,
                    $config
                );
                if ($parameters['esIndex'][$allLangugesId]  !== false) {
                    $params = [
                        'index' => $parameters['esIndex'][$allLangugesId]
                    ];
                    $criteriaForFields = new Criteria();
                    $criteriaHandler->getMergeCriteriaForFields(
                        $criteriaForFields,
                        $channelId,
                        $parameters['language_id']
                    );
                    $fieldsService = $container->get('s_plugin_sisi_search_es_fields.repository');
                    $fieldsconfig = $fieldsService->search($criteriaForFields, $context);
                    $variantsFields = $haendlerVariants->getMappingProductsValues($container, $criteriaForFields);
                    if (!array_key_exists('update', $parameters)) {
                        $synoms = $heandlerSynms->mergeSearchkeywoerter(
                            $config,
                            $productService,
                            $saleschannelContext,
                            $parameters,
                            $output
                        );
                        $settings = $sisiProductService->mergeSettings($fieldsconfig, $config, $synoms);
                        $params['body']['settings'] = $this->produktDataSettings->getSettings($settings);
                        // Pflicht Felder mapping
                        $properties = $sisiProductService->getCheckRequiredFieldInConfig($fieldsconfig, $config);
                        $params['body']['mappings'] = [
                            'properties' => $properties
                        ];
                        $sisiProductService->mergeRequiredField($params, $properties, $config);
                        $mapping = $this->produktDataMapping->getMapping($params['body']['mappings']);
                        $haendlerVariants->fixMappingForvariants($config, $mapping, $variantsFields);
                        $params['body']['mappings'] = $mapping;
                        $this->createindex->setInsert($client, $params);
                    }

                    $criteria = $stepHaendler->getEntities($parameters, $listingSettings, $config);
                    $haendlerVariants->setDBQueryWithvariants($config, $criteria);
                    $this->createCriteria->getCriteria($criteria);
                    $categoryCriteria = new Criteria();
                    // start category id
                    $navigationCategoryId = $salechannelItem->getNavigationCategoryId();
                    $categoryCriteria->addFilter(new EqualsFilter('id', $navigationCategoryId));
                    $criteriaHandler->getOnlyMainProducts($criteria, $config, $parameters);
                    //product_category_tree
                    $criteriaHandler->fixDynamicAccess($criteria, $config);
                    $entities = $productService->search($criteria, $saleschannelContext);
                    $texthaendler->write(
                        $output,
                        "The Next " . $entities->getTotal() . " articles are now being indexed in " . $lanuageName . "\n"
                    );
                    $total = (int)$entities->getTotal();
                    $parameters['connection'] = $connection;
                    $parameters['config'] = $config;
                    $parameters['urlGenerator'] = $this->urlGenerator;
                    $parameters['variantsFields'] = $variantsFields;
                    $parameters['shop'] = $shop;
                    $parameters['createCriteria'] = $this->createCriteria;
                    $parameters['saleschannelContext'] = $saleschannelContext;
                }
            }
            if ($total > 0) {
                // insert the db values
                $parameters['time'] = $time;
                $this->inserProduktDataIndex->setIndex(
                    $entities,
                    $fieldsconfig,
                    $client,
                    $lanugageId,
                    $loggingService,
                    $output,
                    $parameters,
                    $container
                );
                foreach ($lanuagesArray as $allLangugesId) {
                    $parameters['language'] = $indexHaendler->getLanuageNameById($connection, $allLangugesId);
                    $usetime = $indexHaendler->indexFinsih(
                        $parameters,
                        $connection,
                        $contextService,
                        $time,
                        $channelId,
                        $token,
                        $allLangugesId,
                        $output
                    );
                }
                return ['total' => $total, 'usetime' => $usetime];
            }
        }
        return ['total' => $total, 'usetime' => $usetime];
    }

    /**
     * @param array $salechannelItem
     * @return int|string|null
     */
    private function getArrayFirst(array $salechannelItem)
    {
        foreach ($salechannelItem as $key => $unused) {
            return $key;
        }
        return null;
    }
}
