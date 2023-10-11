<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Exception;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\AbstractPropertyGroupSorter;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Event\NestedEventCollection;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;
use Sisi\Search\ESIndexInterfaces\InterfaceInsertProduktDataIndex;
use Sisi\Search\Service\ClientService;
use Sisi\Search\Service\ContextService;
use Sisi\Search\Service\CriteriaService;
use Sisi\Search\Service\IndexService;
use Sisi\Search\Service\InsertTimestampService;
use Sisi\Search\Service\VariantenService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 *
 * @SuppressWarnings(PHPMD)
 */
class WrittenEvents implements EventSubscriberInterface
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
     *
     * @var Logger
     */
    private $loggingService;

    /**
     *
     *   @var ContainerInterface
     */
    protected $container;

    /**
     *
     *
     * @var InterfaceCreateCriteria
     */
    protected $createCriteria;

    /**
     *
     * @var  AbstractSalesChannelContextFactory
     */
    protected $salesChannelContextFactory;

    /**
     * @var AbstractPropertyGroupSorter
     */
    protected $propertyGroupSorter;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     *
     * @var InterfaceInsertProduktDataIndex
     */
    protected $inserProduktDataIndex;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param Logger $loggingService
     * @param ContainerInterface $container,
     * @param InterfaceCreateCriteria $createCriteria
     * @param AbstractSalesChannelContextFactory $salesChannelContextFactory
     * @param UrlGeneratorInterface $urlGenerator
     * @param AbstractPropertyGroupSorter $propertyGroupSorter,
     * @param InterfaceInsertProduktDataIndex $inserProduktDataIndex,
     *
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        Logger $loggingService,
        ContainerInterface $container,
        InterfaceCreateCriteria $createCriteria,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        UrlGeneratorInterface $urlGenerator,
        AbstractPropertyGroupSorter $propertyGroupSorter,
        InterfaceInsertProduktDataIndex $inserProduktDataIndex
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->loggingService = $loggingService;
        $this->container = $container;
        $this->createCriteria = $createCriteria;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->urlGenerator = $urlGenerator;
        $this->propertyGroupSorter = $propertyGroupSorter;
        $this->inserProduktDataIndex = $inserProduktDataIndex;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onWritte',
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param EntityWrittenContainerEvent $event
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @SuppressWarnings(PHPMD)
     */
    public function onWritte(EntityWrittenContainerEvent $event): void
    {
        $clienthaendler = new ClientService();
        $config = $this->systemConfigService->get("SisiSearch.config");
        $strDeleteEvent = true;
        $strync = false;
        $client = null;
        $exclude = [];
        if ($config !== null) {
            if (array_key_exists('strdeleteproduct', $config)) {
                if ($config['strdeleteproduct'] === 'yes') {
                    $strDeleteEvent = false;
                }
            }
            if (array_key_exists('strync', $config)) {
                if ($config['strync'] === 'yes') {
                    $strync = true;
                }
            }
            if (array_key_exists('stryncexclude', $config)) {
                $exclude = explode("\n", $config["stryncexclude"]);
            }
            $events = $event->getEvents();
            $delteResult = $this->getList($events);
            if ($strDeleteEvent) {
                $client = $clienthaendler->createClient($config);
                $context = $event->getContext();
                $elements = $events->getElements();
                $lanuageId = $context->getLanguageId();
                if (array_key_exists('configLanguageIndex', $config)) {
                    if (!empty($config['configLanguageIndex'])) {
                        $lanuageId = $config['configLanguageIndex'];
                    }
                }
                $indexies = $this->findIndexies($this->connection, $lanuageId);
                $this->deleteInaktivefromEsServer($elements, $indexies, $client);
                if (array_key_exists('product.deleted', $delteResult)) {
                    $this->deletefromEsServer($indexies, $delteResult, 'product.deleted', $client);
                }
            }
            if ($strync) {
                if (array_key_exists('product.written', $delteResult)) {
                    $contextService = new ContextService();
                    $haendlerVariants = new VariantenService();
                    $criteriaHandler = new CriteriaService();
                    $timeHaendler = new InsertTimestampService();
                    $indexHaendler = new IndexService();
                    $context = $contextService->getContext();
                    $saleschannel = $this->container->get('sales_channel.repository');
                    $criteriaChannel = new Criteria();
                    $criteriaChannel->addAssociation('languages');
                    $salechannelItems = $saleschannel->search($criteriaChannel, $context)->getEntities()->getElements();
                    foreach ($salechannelItems as $item) {
                        $languagesArray = $item->getLanguages();
                        $channelId = $item->getId();
                        if (!in_array($channelId, $exclude)) {
                            foreach ($languagesArray as $language) {
                                $languageId = $language->getId();
                                if (!in_array($languageId, $exclude)) {
                                    $parameters = [];
                                    $token = $contextService->getRandomHex();
                                    if (array_key_exists('configLanguageIndex', $config)) {
                                        if (!empty($config['configLanguageIndex'])) {
                                            $languageId = $config['configLanguageIndex'];
                                        }
                                    }
                                    $languageIdSelektor = [
                                        SalesChannelContextService::LANGUAGE_ID => strtolower(
                                            $languageId
                                        ),
                                    ];
                                    if (array_key_exists('configLanguage', $config)) {
                                        if (!empty($config['configLanguage'])) {
                                            $languageIdSelektor = [SalesChannelContextService::LANGUAGE_ID => strtolower($config['configLanguage'])];
                                        }
                                    }
                                    $saleschannelContext = $this->salesChannelContextFactory->create(
                                        $token,
                                        $channelId,
                                        $languageIdSelektor
                                    );
                                    $entities = $this->getProducts($delteResult, $saleschannelContext, $config);
                                    if ($entities) {
                                        $criteriaForFields = new Criteria();
                                        $criteriaHandler->getMergeCriteriaForFields(
                                            $criteriaForFields,
                                            $channelId,
                                            $languageId
                                        );
                                        $fieldsService = $this->container->get('s_plugin_sisi_search_es_fields.repository');
                                        $fieldsconfig = $fieldsService->search($criteriaForFields, $context);
                                        $variantsFields = $haendlerVariants->getMappingProductsValues(
                                            $this->container,
                                            $criteriaForFields
                                        );
                                        $lanuageName = $indexHaendler->getLanuageNameById($this->connection, $languageId);
                                        $time = time();
                                        $parameters['language_id'] = $languageId;
                                        $parameters['update'] = "2";
                                        $parameters['language'] = $lanuageName;
                                        $parameters['esIndex'] = [];
                                        $indexName = $timeHaendler->getTheESIndex(
                                            $time,
                                            $parameters,
                                            $this->connection,
                                            $channelId,
                                            $config
                                        );
                                        $parameters['esIndex'][$languageId] = $indexName;
                                        $parameters['time'] = "1";
                                        $parameters['backend'] = "1";
                                        $parameters['isAll'] = 'no';
                                        $parameters['channelId'] = $channelId;
                                        $parameters['categorie_id'] = $item->getNavigationCategoryId();
                                        $parameters['connection'] = $this->connection;
                                        $parameters['config'] = $config;
                                        $parameters['urlGenerator'] = $this->urlGenerator;
                                        $parameters['variantsFields'] = $variantsFields;
                                        $parameters['createCriteria'] = $this->createCriteria;
                                        $parameters['saleschannelContext'] = $saleschannelContext;
                                        $parameters['propertyGroupSorter'] = $this->propertyGroupSorter;
                                        if (!empty($indexName)) {
                                            $this->inserProduktDataIndex->setIndex(
                                                $entities,
                                                $fieldsconfig,
                                                $client,
                                                $languageId,
                                                $this->loggingService,
                                                null,
                                                $parameters,
                                                $this->container
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $productId
     * @return array|bool
     */
    private function getParentId($productId)
    {
        $handler = $this->connection->createQueryBuilder()
            ->select([' HEX(`id`), HEX(`parent_id`)'])
            ->from('product')
            ->Where('`id` = UNHEX(:id)')
            ->setParameter('id', $productId)
            ->setMaxResults(1)
            ->addGroupBy('id');
        return $handler->execute()->fetch();
    }

    /**
     * @param string $productId
     * @return array|bool
     */
    private function getCheckisListed($productId)
    {
        $handler = $this->connection->createQueryBuilder()
            ->select(['custom_fields'])
            ->from('product_translation')
            ->Where('`product_id` = UNHEX(:id)')
            ->setParameter('id', $productId)
            ->setMaxResults(1);
        return $handler->execute()->fetch();
    }

    private function getProducts(array $delteResult, $saleschannelContext, array $config)
    {
        $criteria = new Criteria();
        $this->createCriteria->getCriteria($criteria);
        $productService = $this->container->get('sales_channel.product.repository');
        $or = [];
        foreach ($delteResult['product.written'] as $productId) {
            $parentId = $productId;
            $str = false;
            $strIsVaraints = $this->strVariants($config);
            $product = null;
            if ($strIsVaraints) {
                $product = $this->getParentId($productId);
            }
            if (is_array($product)) {
                if (array_key_exists('addVariants', $config)) {
                    if ($config['addVariants'] === 'yes') {
                        $str = true;
                    }
                    if ($config['addVariants'] === 'individual') {
                        $str = true;
                        $customsFields = $this->getCheckisListed($productId);
                        if (!empty($customsFields) && $customsFields !== null) {
                            if ($customsFields['custom_fields'] !== null) {
                                $customsFields = (array) json_decode($customsFields['custom_fields']);
                                if (array_key_exists('sisi_list', $customsFields)) {
                                    if (!empty($customsFields['sisi_list'])) {
                                        $str = false;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($product['HEX(`parent_id`)'] !== null && $str) {
                    $parentId = $product["HEX(`parent_id`)"];
                }
                $or[] = new EqualsFilter('id', $parentId);
            }
            $criteria->addFilter(
                new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    $or
                )
            );
            return $productService->search($criteria, $saleschannelContext);
        }
    }

    private function strVariants(array $config): bool
    {
        $strIsVaraints = true;
        if (array_key_exists('onlymain', $config)) {
            if ($config['onlymain'] === 'no') {
                $strIsVaraints = false;
            }
        } else {
            $strIsVaraints = false;
        }
        return $strIsVaraints;
    }

    private function deletefromEsServer(array $indexies, array $delteResult, string $key, Client $client)
    {
        foreach ($indexies as $index) {
            foreach ($delteResult[$key] as $productId) {
                $params = [
                    'index' => $index["index"],
                    'id' => $productId,
                ];
                try {
                    $client->delete($params);
                } catch (Exception $e) {
                    $this->loggingService->log(100, $e->getMessage());
                }
            }
        }
    }

    private function deleteInaktivefromEsServer(array $elements, array $indexies, Client $client): void
    {
        foreach ($elements as $element) {
            $payloads = $element->getPayloads();
            if ($payloads != null) {
                foreach ($payloads as $payload) {
                    if (array_key_exists('active', $payload)) {
                        if ($payload['active'] === false) {
                            foreach ($indexies as $index) {
                                $params = [
                                    'index' => $index["index"],
                                    'id' => $payload['id'],
                                ];
                                try {
                                    $client->delete($params);
                                } catch (Exception $e) {
                                    $this->loggingService->log(100, $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function getList(NestedEventCollection $events): array
    {
        $list = [];

        foreach ($events as $event) {
            if ($event instanceof EntityWrittenEvent) {
                $list[$event->getName()] = $event->getIds();
            } else {
                $list[] = $event;
            }
        }

        return $list;
    }

    /**
     * @param Connection $connection
     * @param string|null $language
     *
     * @return mixed
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findIndexies(Connection $connection, string $language = null)
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*, HEX(id), `time`,`index`'])
            ->from('s_plugin_sisi_search_es_index');

        if ($language != null) {
            $handler->andWhere('language=:language');
        }
        $handler->orderBy('s_plugin_sisi_search_es_index.time', 'desc');

        if ($language != null) {
            $handler->setParameter('language', $language);
        }

        return $handler->execute()->fetchAllAssociative();
    }
}
