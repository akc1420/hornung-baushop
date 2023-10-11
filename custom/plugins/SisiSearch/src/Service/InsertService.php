<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Exception;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Class InsertService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD)
 */
class InsertService
{
    /**
     * @param EntitySearchResult $entities
     * @param EntitySearchResult $mappingValues
     * @param Client $client
     * @param string $lanugageId
     * @param Logger $loggingService
     * @param OutputInterface | null $output
     * @param Mixed $insertQuery
     * @param array $parameters
     * @param ContainerInterface $container
     * @param array $merkerIdsFordynamicProducts
     *
     */
    public function setIndex(
        &$entities,
        $mappingValues,
        $client,
        $lanugageId,
        $loggingService,
        $output,
        $insertQuery,
        $parameters,
        $container,
        &$merkerIdsFordynamicProducts
    ): void {
        $total = $entities->getTotal();
        $progress = new ProgressService();
        $heandlermainInsert = new InsertMainMethode();
        $haendlerTime = new InsertTimestampService();
        $heandlerExtendInsert = new ExtendInsertService();
        $heandlercategorie = new CategorieService();
        $texthaendler = new TextService();
        $haendlerVariants = new VariantenService();
        $heandlerMultilanuage = new MultiLanuageServiceService();
        $categoriesValue = $heandlercategorie->getAllCategories($container, $parameters['categorie_id']);
        $counter = 0;
        $mem_usage = memory_get_usage();
        $merker = [];
        $texthaendler->write($output, round($mem_usage / 1048576, 2) . " megabytes \n");
        $variantsFields = $parameters['variantsFields'];
        $categorieMerker = [];
        $propertiesMerker = [];
        $lanuagesArray[] = $parameters['language_id'];
        $fields = [];
        $insertresult = "";
        if ($parameters['isAll'] === 'all') {
            $allLangugesIds = $heandlerMultilanuage->getAllChannelLanguages(
                $parameters['channelId'],
                $parameters['connection']
            );
            $lanuagesArray = [];
            foreach ($allLangugesIds as $allLangugesId) {
                $lanuagesArray[] = $allLangugesId["HEX(langtable.language_id)"];
            }
        }
        foreach ($entities as $entitie) {
            if (
                $heandlerExtendInsert->checkStockFromAllVaraints($entitie, $parameters, $output, $loggingService)
                && $heandlerExtendInsert->checkRemoveDouble($parameters['config'], $merker, $entitie)
            ) {
                foreach ($lanuagesArray as $allLangugesId) {
                    $fields[$allLangugesId] = [];
                }
                $percentage = $counter / $total * 100;
                if (!array_key_exists('backend', $parameters)) {
                    $progress->showProgressBar($percentage, 2, $output);
                }
                foreach ($lanuagesArray as $allLangugesId) {
                    $heandlermainInsert->insert(
                        $mappingValues,
                        $entitie,
                        $allLangugesId,
                        $loggingService,
                        $parameters,
                        $categoriesValue,
                        $categorieMerker,
                        $propertiesMerker,
                        $fields[$allLangugesId]
                    );
                    $haendlerVariants->fixEsInsertForvariants(
                        $fields[$allLangugesId],
                        $entitie,
                        $parameters['config'],
                        $variantsFields,
                        $this,
                        $lanuagesArray,
                        $parameters['saleschannelContext'],
                        $container,
                        $merkerIdsFordynamicProducts,
                        $parameters['urlGenerator']
                    );
                }
                foreach ($lanuagesArray as $allLangugesId) {
                    $haendlerTime->deleteEntry($parameters, $client, $entitie);
                    $insertresult = $insertQuery->insertValue($entitie, $client, $parameters['esIndex'][$allLangugesId], $fields[$allLangugesId]);
                }
                if (count($insertresult) > 0) {
                    if ($insertresult["_shards"]["failed"]) {
                        $loggingService->log('100', 'Insert fail id ' . $insertresult["_id"]);
                    }
                }
            }
        }
        $heandlerExtendInsert->echoLastLine($parameters, $progress, $output);
    }

    /**
     * @param EntitySearchResult<DBFieldsEntity> $mappingValues
     * @param array $fields
     * @param $entitie
     * @param $translation
     * @return void
     */
    public function setDatetype(
        EntitySearchResult &$mappingValues,
        array &$fields,
        $entitie,
        $translation
    ): void {
        foreach ($mappingValues as $mappingValue) {
            $name = 'get' . trim(ucfirst($mappingValue->getName()));
            $tablename = $mappingValue->getTablename();
            $indexName = $tablename . '_' . $mappingValue->getName();
            $indexName = $mappingValue->getPrefix() . $indexName;
            $valueString = "0000-01-01";
            if ($mappingValue->getFieldtype() === 'date') {
                $value = new \DateTime();
                if ($entitie !== null) {
                    if (method_exists($entitie, $name)) {
                        $value = $entitie->$name();
                    }
                    if ($value === null) {
                        if (method_exists($translation, 'getCustomFields')) {
                            $customsFields = $translation->getCustomFields();
                            if ($customsFields !== null) {
                                $key = trim($mappingValue->getName());
                                if (array_key_exists($key, $customsFields)) {
                                    $valueString = $customsFields[$key];
                                }
                            }
                        }
                    }
                }
                $fields[$indexName] = $valueString ;
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings("PMD.CyclomaticComplexity")
     * @param EntitySearchResult<DBFieldsEntity> $mappingValues
     * @param array $fields
     * @param mixed $entitie
     * @param mixed $translation
     * @param Logger $loggingService
     * @param array $config
     * @param string $table
     * @param bool $isArrayFrom
     * @param string|null $parentId
     *
     */

    public function checkFunction(
        EntitySearchResult &$mappingValues,
        array &$fields,
        $entitie,
        $translation,
        Logger $loggingService,
        array $config,
        string $table = 'product',
        bool $isArrayFrom = false,
        $parentId = null,
        array $ext = []
    ): void {
        $productnameValue = '';
        foreach ($mappingValues as $mappingValue) {
            $name = 'get' . trim(ucfirst($mappingValue->getName()));
            $tablename = $mappingValue->getTablename();
            $exlude = $mappingValue->getExclude();
            $heandlerExtendInsert = new ExtendInsertService();
            $heandlerExtendSearch = new ExtSearchService();
            $strInsert = false;
            $isonlymain = $mappingValue->getOnlymain();
            if ($entitie !== null && !empty($entitie)) {
                if (method_exists($entitie, $name) && ($exlude != '1' || $parentId == null) || ($name === "properties_name")) {
                    $strInsert = true;
                }
            }
            if (method_exists($mappingValue, 'getActive')) {
                if (!$mappingValue->getActive()) {
                    $strInsert = false;
                }
            }
            if ($strInsert) {
                $value = $heandlerExtendInsert->getTranslation($translation, $name, $entitie, $ext, $config);
                $value = $this->removeSpecialCharacters($value, $mappingValue);
                $value = $heandlerExtendSearch->stripUrl($value, $config);
                $name = $tablename . '_' . $mappingValue->getName();
                $isCorrectType = $this->checkFieldTypeIsCorrect(
                    $value,
                    $mappingValue->getFieldtype(),
                    $loggingService,
                    $name
                );
                if ($isCorrectType && ($tablename == $table)) {
                    $merge = $mappingValue->getMerge();
                    if ($merge === 'yes' && $name !== "product_name") {
                        $productnameValue .= $this->stripContent($value, $mappingValue);
                    } else {
                        $name = $mappingValue->getPrefix() . $name;
                        $value = $this->stripContent($value, $mappingValue);
                        $fields[$name] = $value;
                        $this->mergeField($isonlymain, $name, $value, $fields, $entitie);
                    }
                }
            } elseif ($isArrayFrom != false && $table == $tablename) {
                $loggingService->log('100', 'Data field not available ' . $name);
            }
        }
        if (array_key_exists('product_name', $fields)) {
            if (!empty($productnameValue)) {
                if (array_key_exists('product_namenest', $fields)) {
                    if (is_array($fields['product_namenest'])) {
                        $text = 'The product name is only available for main products and can’t be use for inside mapping';
                        $loggingService->log('100', $text);
                        throw new Exception($text);
                    }
                }
                $keyName = 'product_name';
                $prefix = '';
                if (array_key_exists('prefixsearchkeywort', $config)) {
                    $prefix = $config['prefixsearchkeywort'];
                }
                if (!empty($prefix)) {
                    $keyName = $prefix . $keyName;
                }
                $fields[$keyName] .= '|' . $productnameValue;
            }
        }
    }

    /**
     * @param string $isonlymain
     * @param string $name
     * @param string $value
     * @param array $fields
     * @param mixed $entitie
     * @return void
     */
    private function mergeField(string $isonlymain, string $name, string $value, array &$fields, $entitie)
    {
        $onlymain = "0";
        if (method_exists($entitie, 'getParentId')) {
            $parentId = $entitie->getParentId();
            if (empty($parentId)) {
                $onlymain = "1";
            }
        }
        if ($isonlymain === 'yes') {
            $fields[$name . "nest"] = [
                $name => $value,
                "onlymain" => $onlymain
            ];
        }
    }

    /**
     * @param string|null $value
     * @param string $fieldType
     * @param Logger $loggingService
     * @param string $name
     * @return bool
     */
    private function checkFieldTypeIsCorrect($value, string $fieldType, Logger $loggingService, string $name): bool
    {
        $type = gettype($value);
        $return = false;
        $fieldTypeValues = [
            'text',
            'keyword',
            'date',
            'integer',
            'float',
            'short',
            'byte'
        ];

        if ($type == null) {
            $loggingService->log('100', 'Data field error value empty');
        }
        if ($type === 'string' && in_array($fieldType, $fieldTypeValues)) {
            $return = true;
        }
        if ($type === 'integer') {
            $return = true;
        }

        if ($type === 'float') {
            $return = true;
        }

        if ($type === 'double') {
            $return = true;
        }

        if ($type === 'long') {
            $return = true;
        }

        if ($type === 'array') {
            $return = true;
        }

        if ($return == false && ($value != null)) {
            $loggingService->log('100', 'Data field error ' . $name);
        }
        return $return;
    }

    /**
     * @param string $content
     * @param DBFieldsEntity $mappingValue
     * @return string|void
     */

    public function stripContent($content, $mappingValue)
    {
        $stripTags = '';
        if (is_array($content)) {
            $content = implode($content);
        }
        if ($mappingValue->getStrip_str() !== 'yes') {
            return trim($content);
        }
        if ($mappingValue->getStrip_str() === 'yes' && !empty($mappingValue->getStrip())) {
            $stripTagsValues = explode(",", $mappingValue->getStrip());
            foreach ($stripTagsValues as $stripTagsValue) {
                $stripTags .= "<" . $stripTagsValue . ">";
            }
        }
        if (!empty($stripTags)) {
            return strip_tags(trim($content), $stripTags);
        } elseif ($mappingValue->getStrip_str() === 'yes') {
            return strip_tags(trim($content));
        }
    }

    /**
     * @param string $content
     * @param DBFieldsEntity $mappingValue
     * @return string|void
     */
    public function removeSpecialCharacters($content, $mappingValue)
    {
        if (!empty($mappingValue->getPhpfilter())) {
            $specialCharaters = explode("\n", $mappingValue->getPhpfilter());
            foreach ($specialCharaters as $special) {
                $content = str_replace($special, "", $content);
            }
        }
        return $content;
    }


    /**
     *
     * @SuppressWarnings("PMD.CyclomaticComplexity")
     * @param array|null $customfields
     * @param EntitySearchResult<DBFieldsEntity> $mappingValues
     * @param array $fields
     * @param Logger $loggingService
     * @param array $config
     * @param string|null $parentId
     */

    public function setCustomsFileds(
        $customfields,
        EntitySearchResult $mappingValues,
        array &$fields,
        Logger $loggingService,
        $config,
        $parentId = null
    ): void {
        if ($customfields != null) {
            $productnameValue = '';
            $heandlerExtendSearch = new ExtSearchService();
            foreach ($customfields as $key => $customfield) {
                foreach ($mappingValues as $mappingValue) {
                    $name = $mappingValue->getName();
                    $exlude = $mappingValue->getExclude();
                    if (!is_string($key) || !is_string($name)) {
                        continue;
                    }
                    if (trim($key) == trim($name)) {
                        $name = $mappingValue->getTablename() . '_' . $name;
                        $isCorrectType = $this->checkFieldTypeIsCorrect(
                            $customfield,
                            $mappingValue->getFieldtype(),
                            $loggingService,
                            $name
                        );
                        if ($isCorrectType && ($exlude != '1' || $parentId == null)) {
                            $value = $this->stripContent($customfield, $mappingValue);
                            $value = $this->removeSpecialCharacters($value, $mappingValue);
                            $value = $heandlerExtendSearch->stripUrl($value, $config);
                            $merge = $mappingValue->getMerge();
                            if ($merge === 'yes' && $name !== "product_name") {
                                $productnameValue .= $this->stripContent(trim($value), $mappingValue);
                            } else {
                                $name = $mappingValue->getPrefix() . $name;
                                $fields[$name] = $this->stripContent(trim($value), $mappingValue);
                            }
                        }
                    }
                }
            }
            if (!empty($productnameValue)) {
                if (array_key_exists('product_name', $fields)) {
                    $fields['product_name'] .= '<br>' . $productnameValue;
                } else {
                    $fields['product_name'] = $productnameValue;
                }
            }
        }
    }
}
