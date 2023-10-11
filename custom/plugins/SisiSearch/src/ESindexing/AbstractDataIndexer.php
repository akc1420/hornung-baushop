<?php

namespace Sisi\Search\ESindexing;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

interface AbstractDataIndexer
{
    public function poppulate(
        Connection $connection,
        ContainerInterface $container,
        SystemConfigService $config,
        QuantityPriceCalculator $priceCalculator,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        Logger $loggingService,
        array $parameters,
        OutputInterface $output
    ): array;
}
