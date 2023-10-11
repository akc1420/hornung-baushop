<?php declare(strict_types=1);


namespace Sensus\Check24Connect\Utils;


use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class Installer
{
    public const SYSTEM_CONFIG_KEY = 'SensusCheck24Connect.config.createdEntities';

    protected const DEFAULT_HEADER_TEMPLATE = <<<EOD
"Bestellnummer";{#- -#}
"Hersteller";{#- -#}
"Herstellernummer";{#- -#}
"Bezeichnung";{#- -#}
"Preis";{#- -#}
"Lieferzeit";{#- -#}
"ProduktLink";{#- -#}
"FotoLink";{#- -#}
"Beschreibung";{#- -#}
"VersandNachnahme";{#- -#}
"VersandKreditkarte";{#- -#}
"VersandLastschrift";{#- -#}
"VersandBankeinzug";{#- -#}
"VersandRechnung";{#- -#}
"VersandVorkasse";{#- -#}
"EANCode";{#- -#}
"Gewicht";{#- -#}
"Lagerbestand"{#- -#}
EOD;


    protected const DEFAULT_BODY_TEMPLATE = <<<EOD
"{{ product.productNumber }}";{#- -#}
"{{ product.manufacturer.translated.name }}";{#- -#}
"{{ product.manufacturerNumber }}"; {#- -#}
"{{ product.translated.name }}";{#- -#}
"{{ product.calculatedPrice.unitPrice|currency }}";{#- -#}
"{%- if product.availableStock >= product.minPurchase and product.deliveryTime -%}
    {{ "detail.deliveryTimeAvailable"|trans({'%name%': product.deliveryTime.translation('name')}) }}{#- -#}
{%- elseif product.availableStock < product.minPurchase and product.deliveryTime and product.restockTime -%}
    {{ "detail.deliveryTimeRestock"|trans({'%count%': product.restockTime,'%restockTime%': product.restockTime,'%name%': product.deliveryTime.translation('name')}) }}{#- -#}
{%- else -%}   
    {{ "detail.soldOut"|trans }}{#- -#}
{%- endif -%}";{#- -#}
"{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}";{#- -#}
"{{ product.cover.media.url }}";{#- -#}
"{{ product.translated.description|raw|length > 300 ? product.translated.description|raw|slice(0,300) ~ '...' : product.translated.description|raw }}";{#- -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ 4.95|currency }}";{#- Change to your delivery costs -#}
"{{ product.ean }}";{#- -#}
"{{ product.weight }}";{#- -#}
"{{ product.availableStock }}"{#- -#}
EOD;

    /** @var EntityRepositoryInterface */
    protected $systemConfigService;

    /** @var EntityRepositoryInterface */
    protected $salesChannelRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $salesChannelRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function install(Context $context): void
    {
        if (!$this->hasExistingComparisonSalesChannel($context)) {
            $this->createSalesChannel($context);
        }
    }

    /**
     * @param Context $context
     * @return bool
     */
    private function hasExistingComparisonSalesChannel(Context $context): bool
    {
        /** @var array|null $config */
        $config = $this->systemConfigService->get(Installer::SYSTEM_CONFIG_KEY);

        if (empty($config)) {
            return false;
        }

        /** @var string[]|null $salesChannelIds */
        $salesChannelIds = $config['sales_channel']; // Entity Name

        if (empty($salesChannelIds)) {
            return false;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $salesChannelIds));

        $result = $this->salesChannelRepository->search($criteria, $context);

        return count($salesChannelIds) === $result->getTotal();
    }

    /**
     * @param Context $context
     * @throws \Exception
     */
    private function createSalesChannel(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->addAssociation('domains');

        /** @var SalesChannelEntity|null $storefrontSalesChannel */
        $storefrontSalesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if (empty($storefrontSalesChannel)) {
            throw new \Exception('[Check24Connect]: Could not find a Storefront Sales Channel, skipped creating a Product Comparison Sales Channel.');
        }

        $storefrontSalesChannelDomainId = $storefrontSalesChannel->getDomains()->first()->getId();

        if (empty($storefrontSalesChannelDomainId)) {
            throw new \Exception(sprintf('[Check24Connect]: Storefront Sales Channel "%s" has no assigned domains, skipped creating a Product Comparison Sales Channel.', $storefrontSalesChannel->getId()));
        }

        $createSalesChannel = [
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'active' => true,
            'countryId' => $storefrontSalesChannel->getCountryId(),
            'currencyId' => $storefrontSalesChannel->getCurrencyId(),
            'customerGroupId' => $storefrontSalesChannel->getCustomerGroupId(),
            'languageId' => $storefrontSalesChannel->getLanguageId(),
            'name' => 'Check24',
            'navigationCategoryId' => $storefrontSalesChannel->getNavigationCategoryId(),
            'paymentMethodId' => $storefrontSalesChannel->getPaymentMethodId(),
            'shippingMethodId' => $storefrontSalesChannel->getShippingMethodId(),
            'taxCalculationType' => 'horizontal',
            'typeId' => Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON,
            'productExports' => [
                [
                    'accessKey' => AccessKeyHelper::generateAccessKey('product-export'),
                    'currencyId' => $storefrontSalesChannel->getCurrencyId(),
                    'encoding' => 'UTF-8',
                    'fileFormat' => 'csv',
                    'fileName' => 'check24.csv',
                    'generateByCronjob' => false,
                    'interval' => 86400,
                    'productStream' => [
                        'name' => 'Check24',
                        'filters' => [
                            [
                                'operator' => 'OR',
                                'type' => 'multi',
                                'position' => 0,
                                'queries' => [
                                    [
                                        'operator' => 'AND',
                                        'type' => 'multi',
                                        'position' => 0,
                                        'queries' => [
                                            [
                                                'field' => 'active',
                                                'type' => 'equals',
                                                'value' => '1',
                                                'position' => 0,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'salesChannelDomainId' => $storefrontSalesChannelDomainId,
                    'storefrontSalesChannelId' => $storefrontSalesChannel->getId(),
                    'headerTemplate' => Installer::DEFAULT_HEADER_TEMPLATE,
                    'bodyTemplate' => Installer::DEFAULT_BODY_TEMPLATE
                ]
            ]
        ];

        try {
            $event = $this->salesChannelRepository->create([$createSalesChannel], $context);
            $this->saveCreatedEntities($event);
        } catch (\Throwable $throwable) {
            throw new \Exception('[Check24Connect]: Failed to create Product Comparison Sales Channel. ' . $throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @param EntityWrittenContainerEvent $event
     * @param SystemConfigService $systemConfigService
     * @throws \Exception
     */
    private function saveCreatedEntities(EntityWrittenContainerEvent $event): void
    {
        $createdEntities = [];
        foreach ($event->getEvents()->getIterator() as $event) {
            /** @var string[]|array|null $ids */
            $entityIds = $event->getIds();
            $entityName = $event->getEntityName();

            /**
             * We skip some entities because they are "_translations" or undeleteable entities,
             * which are automatically removed by Shopware when the parent is deleted
             * and you get an error when you try to delete these on your own
             */
            $deleteableEntity = $this->isStringArray($entityIds) && $entityName !== 'product_stream_filter';

            if (!empty($entityIds) && $deleteableEntity) {
                $createdEntities[$entityName] = $event->getIds();
            }
        }

        $this->systemConfigService->set(Installer::SYSTEM_CONFIG_KEY, $createdEntities);
    }

    /**
     * @param array $array
     * @return bool
     */
    private function isStringArray(array $array): bool
    {
        return count(array_filter($array, 'is_string')) > 0;
    }
}