<?php declare(strict_types=1);


namespace phpschmied\CustomersAlsoBought\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElement;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElementCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\Snippet\SnippetService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\CrossSelling\CrossSellingLoaderResult;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class productLoad implements EventSubscriberInterface
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    protected $container;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderLineItemRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SnippetService
     */
    private $snippetService;

    public function __construct(
        Container                       $container,
        EntityRepositoryInterface       $entityRepository,
        EntityRepositoryInterface       $entityRepository2,
        SalesChannelRepositoryInterface $entityRepository3,
        SystemConfigService             $systemConfigService,
        SnippetService                  $snippetService
    )
    {
        $this->container = $container;
        $this->orderLineItemRepository = $entityRepository;
        $this->orderRepository = $entityRepository2;
        $this->productRepository = $entityRepository3;
        $this->systemConfigService = $systemConfigService;
        $this->snippetService = $snippetService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ProductPageLoadedEvent::class => 'onLoadProduct',
        ];
    }

    public function onLoadProduct(ProductPageLoadedEvent $event)
    {
        $product = $event->getPage()->getProduct();

        if(isset($product->getCustomFields()['show_customer_also_bought']) && $product->getCustomFields()['show_customer_also_bought'] === true) {
            return;
        }

        //If is set a slider limit
        $tmp_limit = $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.limit');

        //validate the limit value
        if ($tmp_limit !== null && $tmp_limit !== '' && is_int((int)$tmp_limit) && (int)$tmp_limit >= 0) {
            if ((int)$tmp_limit <= 6) {
                $limit = (int)$tmp_limit;
            } else {
                $limit = 6;
            }
        } else {
            $limit = 0;
        }

        $snippet = $this->getSnippet($event);

        //if limit higher than null
        if ($limit > 0) {
            $product_ids = [];
            $connection = $this->container->get(Connection::class);

            $query = "SELECT LCASE(HEX(oli.product_id)) as product_id, COUNT(oli.product_id) AS `count`
                FROM order_line_item AS oli
                JOIN product AS p ON oli.product_id = p.id AND p.id != 0x" . $product->getId() . "
                WHERE
                    oli.order_id IN (
                    SELECT oli.order_id
                        FROM order_line_item AS oli
                        WHERE oli.product_id = 0x" . $product->getId() . "
                        GROUP BY oli.order_id
                    )
                GROUP BY oli.product_id
                ORDER BY `count` DESC
                LIMIT 0, " . $limit;

            $result = $connection->query($query)->fetchAll();

            //if result not empty
            if (count($result) > 0) {
                //Load the product
                $products = $this->getProduct($result, $event);

                if (is_object($products)) {
                    $cross_selling = new CrossSellingElementCollection();
                    $product_collection = new ProductCollection();
                    $product_cross_selling_entity = new ProductCrossSellingEntity();
                    $product_cross_selling_entity->setId(Uuid::randomHex());
                    $product_cross_selling_entity->setActive(true);
                    $product_cross_selling_entity->setLimit((int)$this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.limit'));
                    $product_cross_selling_entity->setPosition(1);
                    $product_cross_selling_entity->setProductId($product->getId());
                    $product_cross_selling_entity->setType('productList');
                    $product_cross_selling_entity->setTranslated(
                        [
                            'name' => $snippet['phpSchmied.customersalsobought.headline']
                        ]
                    );
                    $product_cross_selling_entity->setName($snippet['phpSchmied.customersalsobought.headline']);

                    foreach ($products->getEntities()->getElements() as $cross_sell_product) {
                        $show = false;
                        /**@var $cross_sell_product ProductEntity* */

                        if(
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.inactive') !== null &&
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.inactive') === true &&
                            !$cross_sell_product->getActive()
                        ) {
                            continue;
                        }

                        if(
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.abverkauf') !== null &&
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.abverkauf') === true &&
                            !$cross_sell_product->getStock()
                        ) {
                            continue;
                        }

                        if (
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.categories') !== null &&
                            is_array($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.categories')) &&
                            count($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.categories'))
                        ) {
                            foreach ($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.categories') as $category_id) {
                                if (in_array($category_id, $cross_sell_product->getCategoryTree())) {
                                    $show = true;
                                }
                            }
                        } else {
                            $show = true;
                        }

                        /**@var $cross_sell_product ProductEntity* */
                        if (
                            $this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.disabledCategories') !== null &&
                            is_array($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.disabledCategories')) &&
                            count($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.disabledCategories'))
                        ) {
                            foreach ($this->systemConfigService->get('phpschmiedCustomersAlsoBought.config.disabledCategories') as $category_id) {
                                if($cross_sell_product->getCategoryTree() !== null) {
                                    if (in_array($category_id, $cross_sell_product->getCategoryTree())) {
                                        $show = false;
                                    }
                                }
                            }
                        }

                        if ($show) {
                            $product_collection->add($cross_sell_product);
                        }
                    }

                    //Is any crossSelling element in use
                    if ($event->getPage()->getCmsPage() === null) {
                        if ($event->getPage()->getCrossSellings() instanceof CrossSellingLoaderResult) {
                            $cross_selling_element = new \Shopware\Storefront\Page\Product\CrossSelling\CrossSellingElement();
                        } else {
                            $cross_selling_element = new CrossSellingElement();
                        }
                    } else {
                        $cross_selling_element = new CrossSellingElement();
                    }

                    $cross_selling_element->setProducts($product_collection);
                    $cross_selling_element->setCrossSelling($product_cross_selling_entity);
                    $cross_selling->add($cross_selling_element);

                    $cross_selling_element->setTotal($products->count());

                    //Is any crossSelling element in use
                    if ($event->getPage()->getCmsPage() !== null) {
                        foreach ($event->getPage()->getCmsPage()->getSections()->getBlocks() as $block) {
                            if ($block->getType() === 'cross-selling') {
                                foreach ($block->getSlots() as $slot) {
                                    $cs = $slot->getData()->getVars();
                                    if (isset($cs['crossSellings']) && $cs['crossSellings'] !== null) {
                                        $cs['crossSellings']->add($cross_selling_element);
                                    } else {
                                        $crossSellingStruct = new CrossSellingStruct();
                                        $csCollection = new CrossSellingElementCollection();
                                        $csCollection->add($cross_selling_element);
                                        $crossSellingStruct->setCrossSellings($csCollection);
                                        $slot->setData($crossSellingStruct);
                                    }
                                }
                            }
                        }
                    } else {
                        if (count($product_collection->getElements())) {
                            $event->getPage()->getCrossSellings()->add($cross_selling_element);
                        }
                    }
                }
            }
        }
    }

    /**
     * Loads the product by product id
     * @param $products_array null|array
     * @param ProductPageLoadedEvent $event
     * @param int $limit
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function getProduct(?array $products_array, ProductPageLoadedEvent $event)
    {
        $ids = [];

        foreach ($products_array as $product) {
            $ids[] = $product['product_id'];
        }

        $criteria = new Criteria($ids);
        $criteria->addAssociation('cover');
        return $this->productRepository->search($criteria, $event->getSalesChannelContext());
    }

    private function getSnippet(ProductPageLoadedEvent $event)
    {
        $languageId = $event->getSalesChannelContext()->getSalesChannel()->getLanguageId();

        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->container->get('language.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $languageId));
        $language = $languageRepository->search($criteria, $event->getContext())->first();

        /** @var EntityRepository $localeRepository */
        $localeRepository = $this->container->get('locale.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $language->getLocaleId()));
        $locale = $localeRepository->search($criteria, $event->getContext())->first();

        $snippet_sets = $this->snippetService->getSnippetSet(
            $event->getSalesChannelContext()->getSalesChannel()->getId(),
            $event->getSalesChannelContext()->getSalesChannel()->getLanguageId(),
            $locale->getCode(),
            $event->getContext()
        );

        $translator = $this->container->get('translator');
        $catalog = $translator->getCatalogue($locale->getCode());

        $snippet = $this->snippetService->getStorefrontSnippets(
            $catalog,
            $snippet_sets->getId()
        );

        return $snippet;
    }
}
