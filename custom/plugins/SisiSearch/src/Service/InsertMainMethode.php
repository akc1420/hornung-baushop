<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bridge\Monolog\Logger;

/**
 *
 *  @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class InsertMainMethode
{
    /**
     * @param EntitySearchResult $mappingValues
     * @param SalesChannelProductEntity $entitie
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param array $parameters
     * @param array $categoriesValue
     * @param array $categorieMerker
     * @param array $propertiesMerker
     * @param array $fields
     * @return void
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function insert(
        EntitySearchResult $mappingValues,
        SalesChannelProductEntity $entitie,
        string $lanugageId,
        Logger $loggingService,
        array $parameters,
        array $categoriesValue,
        array &$categorieMerker,
        array &$propertiesMerker,
        array &$fields
    ): void {
        $haendlerTranslation = new TranslationService();
        $heandlerExtendInsert = new ExtendInsertService();
        $heandlercategorie = new CategorieService();
        $heandlerSynms = new SearchkeyService();
        $heandlerChannelData = new ChannelDataService();
        $heandlerIndex = new IndexService();
        $heandlerprice = new PriceService();
        $heandlerPropterties = new PropertiesService();
        $heandlerinsert = new InsertService();
        $parentId = trim($entitie->getParentId());
        $translation = $haendlerTranslation->getTranslationfields($entitie->getTranslations(), $lanugageId, $parameters['config']);
        $heandlerinsert->checkFunction(
            $mappingValues,
            $fields,
            $entitie,
            $translation,
            $loggingService,
            $parameters['config'],
            'product',
            false,
            $parentId,
            []
        );
        $heandlerinsert->setDatetype($mappingValues, $fields, $entitie, $translation);
        if ($translation) {
            $heandlerinsert->setCustomsFileds(
                $translation->getCustomFields(),
                $mappingValues,
                $fields,
                $loggingService,
                $parameters['config'],
                $parentId
            );
        }
        $heandlerSynms->insertSearchkey(
            $fields,
            $entitie,
            $lanugageId,
            $mappingValues,
            $parameters['config'],
            $loggingService,
            $parentId,
            $heandlerinsert,
            $parameters['connection']
        );
        if ($heandlercategorie->strIndexCategorie($parameters['config'])) {
            $categoieStream = $heandlercategorie->getProductStreamsCategories($entitie);
            $categories = $entitie->getCategories();
            $heandlercategorie->getMergeCategories($categories, $categoieStream);
            foreach ($categories as $categorie) {
                $params['categorie'] = $categorie;
                $params['lanugageId'] = $lanugageId;
                $params['categoriesValue'] = $categoriesValue;
                $params['parentid'] = $parentId;
                $params['config'] = $parameters['config'];
                $params['categories'] = $categories;
                $heandlerExtendInsert->insertCategorie(
                    $haendlerTranslation,
                    $params,
                    $mappingValues,
                    $fields,
                    $loggingService,
                    $heandlerinsert,
                    $categorieMerker
                );
            }
        }
        $manufacturers = $entitie->getManufacturer();
        if ($heandlerIndex->checkManufacturer($manufacturers, $fields)) {
            $heandlerExtendInsert->setManufacturerValue(
                $heandlerinsert,
                $manufacturers,
                $parameters['config'],
                $haendlerTranslation,
                $mappingValues,
                $fields,
                $loggingService,
                $lanugageId,
                $parentId
            );
        }
        $fields['id'] = $entitie->getId();
        $fields['language'] = $lanugageId;
        $fields['channel'] = $heandlerChannelData->getDatas($entitie, $parameters['config'], $lanugageId, $parameters['urlGenerator']);
        $fields['properties'] = [];
        $heandlerPropterties->setSortedProperties($fields, $entitie, $parameters);
        if ($entitie->getSortedProperties()) {
            foreach ($entitie->getSortedProperties() as $property) {
                $paramsPro['property'] = $property;
                $paramsPro['lanugageId'] = $lanugageId;
                $paramsPro['parentid'] = $parentId;
                $paramsPro['config'] = $parameters['config'];
                $heandlerExtendInsert->insertProperties(
                    $haendlerTranslation,
                    $paramsPro,
                    $fields,
                    $mappingValues,
                    $loggingService,
                    $heandlerinsert,
                    $propertiesMerker
                );
            }
        }
        $heandlerExtendInsert->addSuggesterField($parameters['config'], $fields);
        $heandlerprice->insertPrice($entitie, $fields);
    }

    public function getDynamicproduct(
        Criteria &$criteria,
        array $merkerIdsFordynamicProducts,
        ContainerInterface $container,
        SalesChannelContext $saleschannelContext
    ) {
        $filter = [];
        foreach ($merkerIdsFordynamicProducts as $product) {
            $filter[] = new EqualsFilter('id', $product);
        }
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $filter));
        $productService = $container->get('sales_channel.product.repository');
        return $productService->search($criteria, $saleschannelContext);
    }
}
