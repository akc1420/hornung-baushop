<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeleteInaktiveService
{
    /**
     * @param $parameters
     * @param OutputInterface|null $output
     * @param Connection $connection
     * @param SystemConfigService $systemconfig
     * @param Logger $loggingService
     * @param ContainerInterface $container
     * @return int
     * @throws Exception
     */
    public function deleteIndex(
        $parameters,
        $output,
        $connection,
        $systemconfig,
        $loggingService,
        $container
    ): int {
        $texthaendler = new TextService();
        $criteriaHandler = new CriteriaService();
        $contexthaendler = new ContextService();
        $indexHaendler = new IndexService();
        $transHaendler = new TranslationService();
        $timeHaendler = new InsertTimestampService();
        $heandlerClient = new ClientService();
        $haendlerExDelte = new ExInaktiveExService();
        $texthaendler->write($output, 'Delete Start ..');
        $context = $contexthaendler->getContext();
        $criteriaChannel = new Criteria();
        $time = time();
        $saleschannel = $container->get('sales_channel.repository');
        $shop = "";
        if (array_key_exists('shop', $parameters)) {
            $shop = $parameters['shop'];
            // string manipulation extract channel
            $shop = str_replace("shop=", "", $shop);
        }
        if (array_key_exists('shopID', $parameters)) {
            $shop = "shopID=" . $parameters['shopID'];
        }
        $criteriaHandler->getMergeCriteriaForSalesChannel($criteriaChannel, $shop);
        $lanugageValues = $transHaendler->getLanguageId($parameters, $connection, $output, $loggingService);
        $salechannelItem = $saleschannel->search($criteriaChannel, $context)->getEntities()->first();
        $channelId = $salechannelItem->getId();
        $config = $systemconfig->get("SisiSearch.config", $channelId);
        $lanugageId = $transHaendler->chechIsSetLanuageId($lanugageValues, $salechannelItem, $parameters);
        $parameters['language_id'] = $lanugageId;
        $lanuageName = $indexHaendler->getLanuageNameById($connection, $lanugageId);
        $parameters['language'] = $lanuageName;
        $parameters['channelId'] = $channelId;
        $parameters['update'] = "1";
        $parameters['esIndex'] = $timeHaendler->getTheESIndex($time, $parameters, $connection, $channelId, $config);
        $client = $heandlerClient->createClient($config);
        $str = true;
        $parameters['offset'] = 0;
        while ($str) {
            $products = $haendlerExDelte->getAllInaktiveProducts($connection, $parameters);
            $count = count($products);
            if ($count <= 0) {
                $str = false;
            }
            $parameters['offset'] = $parameters['offset'] + $count;
            $haendlerExDelte->delteInESServer($products, $client, $parameters['esIndex'], $loggingService);
        }
        $texthaendler->write($output, "Delete process now are finish");
        return 0;
    }
}
