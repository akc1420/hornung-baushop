<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationCollection;

class TranslationService
{

    /**
     * @param CategoryTranslationCollection|ProductTranslationCollection|ProductManufacturerCollection|ProductManufacturerTranslationCollection $translation
     * @param string $languageId
     * @param array $config
     * @return false|mixed
     */
    public function getTranslationfields($translation, string $languageId, array $config = [])
    {
        if (array_key_exists('configLanguage', $config)) {
            if (!empty($config['configLanguage'])) {
                $languageId = $config['configLanguage'];
            }
        }
        if ($translation !== null) {
            if (method_exists($translation, 'getElements')) {
                $translationValues = $translation->getElements();
                foreach ($translationValues as $value) {
                    if (strtoupper($value->getLanguageId()) == strtoupper($languageId)) {
                        return $value;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param array $values
     * @param SalesChannelEntity $salechannelItem
     * @return mixed|string
     */
    public function chechIsSetLanuageId(array $values, SalesChannelEntity $salechannelItem, array $parameters = [])
    {
        if (array_key_exists('languageID', $parameters)) {
            return strtolower($parameters['languageID']);
        }
        if (array_key_exists('hex(id)', $values)) {
            return $values['hex(id)'];
        } else {
            return $salechannelItem->getLanguageId();
        }
    }

    /**
     * @param array $parameters
     * @param Connection $connection
     * @param OutputInterface| null $output
     * @param Logger $loggingService
     * @return array|mixed
     *
     *
     */
    public function getLanguageId(
        array $parameters,
        $connection,
        $output,
        $loggingService
    ) {
        if (array_key_exists('language', $parameters)) {
            if ($parameters['language'] === 'all') {
                return 'all';
            }
        }
        if (array_key_exists('language', $parameters) && !array_key_exists('languageID', $parameters)) {
            $result = $this->getLanguage($connection, $parameters['language']);
            if ($result == null) {
                $message = "Language paramter not found";
                if ($output !== null) {
                    $output->writeln($message);
                }
                $loggingService->log('100', $message);
            }
            return $result;
        }
        return [1];
    }

    /**
     * @param Connection $connection
     * @param string $langageName
     * @return mixed
     */
    private function getLanguage(Connection $connection, string $langageName)
    {
        $query = $connection->createQueryBuilder()
            ->select(['hex(id),name'])
            ->from('language')
            ->where('language.name =:name')
            ->setParameter(':name', $langageName);
        $result = $query->execute();
        return $result->fetch();
    }
}
