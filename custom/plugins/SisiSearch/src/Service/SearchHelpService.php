<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SearchHelpService
{
    public function redirectbyOne(ContainerInterface $container, array $newResult): void
    {
        $route = 'frontend.detail.page';
        $hit = array_shift($newResult['hits']["hits"]);
        $id = $hit['_id'];
        if (array_key_exists('_id', $hit['_source'])) {
            $id = $hit['_source']['_id'];
        }
        $url = $container->get('router')->generate($route, ['productId' => $id]);
        $redirekthaendler = new RedirectResponse($url, 301);
        $redirekthaendler->send();
    }

    public function sortManufacturer(array $products): array
    {
        $return = [];
        $hits = $products['hits']['hits'];
        foreach ($hits as $hit) {
            $return[] = $hit;
        }
        return $return;
    }

    public function getChanelName(SalesChannelEntity $saleschannel, ContainerInterface $container): string
    {
        $id = $saleschannel->getId();
        $handler = $container->get('sales_channel.repository');
        $criteria = new Criteria([$id]);
        $contextService = new ContextService();
        $context = $contextService->getContext();
        $salechannelItem = $handler->search($criteria, $context)->getEntities()->getElements();
        return $this->getArrayFirst($salechannelItem);
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param array $salechannelItem
     * @return int|string|null
     */
    private function getArrayFirst(array $salechannelItem)
    {
        foreach ($salechannelItem as $key => $unused) {
            return $unused->getName();
        }
        return null;
    }

    /**
     * @param string|null $saleschannelName
     * @param Logger $logger
     */
    public function checkSalesNameIsEmpty($saleschannelName, $logger): void
    {
        if (empty($saleschannelName)) {
            $logger->log('100', 'Channel name is empty please check the name for your channel in all language');
        }
    }

    /**
     * @param int $size
     * @param string|int|null $page
     * @return int
     */
    public function getFromvalue($size, $page)
    {
        if (empty($size) || !is_numeric($size)) {
            $size = 10;
        }
        if (($page === null) || !is_numeric($page)) {
            $page = 0;
        }
        $page = (int)$page;
        if ($page > 0) {
            $page--;
        }

        return $page * $size;
    }


    public function checkIsPropertie(array $values): array
    {
        $merker = [];
        $return = [];
        foreach ($values as $value) {
            if (!in_array($value["_source"]["properties_name"], $merker)) {
                $return[] = $value;
                $merker[] = $value["_source"]["properties_name"];
            }
        }
        return $return;
    }

    public function checkIsManufatory(array $values): array
    {
        $merker = [];
        $return = [];
        foreach ($values as $value) {
            if (!in_array($value["_source"]["manufacturer_name"], $merker)) {
                $return[] = $value;
                $merker[] = $value["_source"]["manufacturer_name"];
            }
        }
        return $return;
    }


    public function checkIsCategorie(array $values): array
    {
        $merker = [];
        $return = [];
        foreach ($values as $value) {
            if (!in_array($value["_source"]["category_id"], $merker)) {
                $return[] = $value;
                $merker[] = $value["_source"]["category_id"];
            }
        }
        return $return;
    }

    /**
     * @param Connection $connection
     * @param string|null $shopId
     * @param string|null $language
     * @param array $config
     *
     * @return mixed
     */
    public function findLast(Connection $connection, string $shopId = null, string $language = null, $config = [])
    {
        $handler = $connection->createQueryBuilder()
            ->select(['*, HEX(id), `time`,`index`'])
            ->from('s_plugin_sisi_search_es_index')
            ->setMaxResults(1);

        if ($shopId != null) {
            $handler->andWhere('shop=:shop');
        }

        if ($language != null) {
            $handler->andWhere('language=:language');
        }
        if ($config != null) {
            if (array_key_exists('strindexfinish', $config)) {
                if ($config['strindexfinish'] === 'yes') {
                    $handler->andWhere('isfinish=:isfinish');
                    $handler->setParameter('isfinish', 1);
                }
            }
        }
        $handler->orderBy('s_plugin_sisi_search_es_index.time', 'desc');

        if ($shopId != null) {
            $handler->setParameter('shop', $shopId);
        }
        if ($language != null) {
            $handler->setParameter('language', $language);
        }

        return $handler->execute()->fetch();
    }

    /**
     * @param array $terms
     * @param EntitySearchResult<DBFieldsEntity> $fieldsconfig
     * @param array $fields
     * @param array $config
     * @param string $match
     * @return void
     */
    public function getFields(
        array $terms,
        EntitySearchResult $fieldsconfig,
        array &$fields,
        array $config,
        string $match
    ): void {
        $indexProducts = 0;
        $search = $terms['product'];
        $queryheandler = new QueryService();
        $hanlerExSearchService = new ExtSearchService();
        $countInner = 0;
        foreach ($fieldsconfig as $row) {
            $booster = $row->getBooster();
            $this->getBooster($booster);
            $name = $this->setField($row);
            $tablename = $row->getTablename();
            $str = $hanlerExSearchService->strQueryFields($tablename, $config);
            $exclude = $row->getExcludesearch();
            if ($exclude === 'yes') {
                $str = false;
            }
            if ($str) {
                $queryheandler->mergeFields($indexProducts, $fields, $match, $search, $row, $name, $terms);
            }
        }
    }

    public function getBooster(string &$booster): void
    {
        if (is_numeric($booster) && $booster != '0') {
            $booster = "^" . $booster;
        } else {
            $booster = '';
        }
    }

    public function setField(DBFieldsEntity $row): string
    {
        return trim($row->getPrefix() . $row->getTablename()) . '_' . trim($row->getName());
    }
}
