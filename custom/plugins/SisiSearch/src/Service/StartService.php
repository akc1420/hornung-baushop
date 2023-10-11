<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\ESindexing\ProduktDataIndexer;
use Sisi\Search\ServicesInterfaces\InterfaceSearchCategorieService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

class StartService
{
    /**
     * @param SystemConfigService $config
     * @param ProduktDataIndexer $produktDataindexer
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param QuantityPriceCalculator $priceCalculator
     * @param AbstractSalesChannelContextFactory $salesChannelContextFactory
     * @param Logger $loggingService
     * @param array $paramters
     * @param OutputInterface | null $output
     * @param InterfaceSearchCategorieService $searchCategorieService
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *@SuppressWarnings(PHPMD.NPathComplexity)
     **/
    public function startTheIndexing(
        $config,
        $produktDataindexer,
        $connection,
        $container,
        $priceCalculator,
        $salesChannelContextFactory,
        $loggingService,
        $paramters,
        $output,
        $searchCategorieService
    ): void {
        $str = true;
        $index = 0;
        $count = 0;
        $texthaendler = new TextService();
        $heandler = new CategorieIndexService();
        $insertTime = 0;
        if (array_key_exists('offset', $paramters)) {
            $index  =  (int) $paramters['offset'] / $paramters['limit'];
            $count = (int) $paramters['offset'];
        }
        while ($str) {
            if ($index != 0) {
                if (!array_key_exists('update', $paramters)) {
                    $paramters['update'] = "1";
                }
            }
            $paramters['offset'] = $index * $paramters['limit'];
            $paramters['backend'] = "1";
            $paramters['counter'] = $index;
            $returnValue = $produktDataindexer->poppulate(
                $connection,
                $container,
                $config,
                $priceCalculator,
                $salesChannelContextFactory,
                $loggingService,
                $paramters,
                $output
            );
            $total = $returnValue['total'];
            $configExtra = $config->get("SisiSearch.config");
            if (array_key_exists('categorien', $configExtra) && $index == 0) {
                if ($configExtra['categorien'] === "6" || $configExtra['categorien'] === "7" ||  $configExtra['categorien'] === "8") {
                    $heandler->startIndex($container, $paramters, $connection, $config, $output, $loggingService, $searchCategorieService);
                }
            }
            if ($returnValue['usetime'] > 0) {
                $insertTime = $returnValue['usetime'];
            }
            $count += $total;
            $texthaendler->write($output, "Now " . $count . " products in the Index");
            $index++;
            if ($output == null) {
                echo "The next " . $count . " articles are now being indexed  \n";
            }
            if ($total <= 0) {
                $str = false;
            }
        }
        $this->setFinsihflag($insertTime, $connection);
        $texthaendler->write($output, "The index process is finish with " . $count . " products");
    }

    private function setFinsihflag(int $insertTime, Connection $connection): void
    {
        if ($insertTime > 0) {
            $sql = "UPDATE `s_plugin_sisi_search_es_index`
            SET
              `isfinish` = :isfinish
              WHERE time = :time";
            $connection->executeStatement(
                $sql,
                [
                    'isfinish' => 1,
                    'time' => $insertTime
                ]
            );
        }
    }
}
