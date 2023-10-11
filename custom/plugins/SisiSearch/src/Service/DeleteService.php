<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteService
{
    /**
     * @param array $options
     * @param OutputInterface|null $output
     * @param Connection $connection
     * @param SystemConfigService $configValues
     * @param Logger $loggingService
     * @param ContainerInterface $container
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteIndex(
        $options,
        $output,
        $connection,
        $configValues,
        $loggingService,
        $container
    ): int {
        $texthaendler = new TextService();
        $criteriaHandler = new CriteriaService();
        $contexthaendler = new ContextService();
        $indexHaendler = new IndexService();
        $haendlerDelete = new CategorieIndexMappingService();
        $heandlerclient = new ClientService();
        $texthaendler->write($output, 'Delete Start ..');
        $paramters = $texthaendler->stripOption2($options);
        $language = $this->getLanuageID($connection, $paramters);
        $context = $contexthaendler->getContext();
        $shop = $paramters['shop'];
        $saleschannel = $container->get('sales_channel.repository');
        $criteriaChannel = new Criteria();
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->first();
        $language = $this->chechIsSetLanuageId($language, $salechannelItem);
        $config = $configValues->get("SisiSearch.config", $salechannelItem->getId());
        $client = $heandlerclient->createClient($config);
        $paramters['language_id'] = $language;
        if (array_key_exists('languageID', $paramters)) {
            $paramters['language_id'] = $paramters['languageID'];
            $paramters['language']  = $indexHaendler->getLanuageNameById($connection, $paramters['languageID']);
        }
        if (array_key_exists('all', $paramters)) {
            $allNr = (int)$paramters['all'];
            $indexEntieties = $this->getIndexes($connection, $paramters, $salechannelItem->getId());
            $count = count($indexEntieties);
            if ($count == 0) {
                $texthaendler->write($output, "No Index found");
                $loggingService->log(100, "No Index found");
            }
            foreach ($indexEntieties as $key => $entieties) {
                $index = $this->mergeIndex($entieties);
                if (($key + $allNr) < $count) {
                    $haendlerDelete->delteIndex($client, $index, $loggingService);
                    $haendlerDelete->delteIndex($client, "content_" . $index, $loggingService);
                    $haendlerDelete->delteIndex($client, "categorien_" . $index, $loggingService);
                    $connection->executeStatement(
                        'DELETE FROM `s_plugin_sisi_search_es_index` WHERE s_plugin_sisi_search_es_index.time = :time',
                        ['time' => $entieties['time']]
                    );
                }
            }
        }
        $texthaendler->write($output, "Delete process now are finish");
        return 0;
    }


    /**
     * @param string|bool $languageId
     * @param SalesChannelEntity $salechannelItem
     * @return mixed|string
     */
    private function chechIsSetLanuageId($languageId, SalesChannelEntity $salechannelItem)
    {
        if ($languageId === false) {
            return $salechannelItem->getLanguageId();
        } else {
            return $languageId;
        }
    }

    private function mergeIndex(array $entieties): string
    {
        return $entieties['index'];
    }

    /**
     * @param Connection $connection
     * @param array $parameters
     * @return mixed[]
     */

    private function getIndexes(connection $connection, array $parameters, string $shopId)
    {
        $languageId = (string)$parameters['language_id'];
        $query = $connection->createQueryBuilder()
            ->select('HEX(`id`) AS `id`, `time`,`index`')
            ->from(' s_plugin_sisi_search_es_index');

        if (array_key_exists('language', $parameters) && $parameters['language'] != null) {
            $query->andWhere('language = :language');
        }
        $query->andWhere('shop = :shop');
        $query->orderBy('time', 'ASC');
        if (array_key_exists('language', $parameters) && $parameters['language'] != null) {
            $query->setParameter('language', $languageId);
        }
        $query->setParameter('shop', strtolower($shopId));
        return $query->execute()->fetchAll();
    }

    /**
     * @param Connection $connection
     * @param array $parameters
     * @return false|mixed
     */

    private function getLanuageID(connection $connection, array $parameters)
    {

        if (array_key_exists('language', $parameters)) {
            $query = $connection->createQueryBuilder()
                ->select('HEX(`id`) AS `id`, name')
                ->from('language')
                ->andWhere('name =:language')
                ->setParameter('language', $parameters['language']);
            return $query->execute()->fetchColumn();
        }
        return false;
    }
}
