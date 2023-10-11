<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Sisi\Search\Service\FrontendService;
use Sisi\Search\Services\ElasticsearchService;
use Sisi\Search\ServicesInterfaces\InterfaceFrontendService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class CheckoutEvents implements EventSubscriberInterface
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @var Connection
     */
    private $connection;


    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     *
     * @var Logger
     */
    private $loggingService;

    /**
     *
     * @var InterfaceFrontendService
     */
    private $frontendService;


    /**
     * @param SystemConfigService $systemConfigService
     * @param Connection $connection
     * @param ContainerInterface $container
     * @param Logger $loggingService
     * @param InterfaceFrontendService $frontendService
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Connection $connection,
        ContainerInterface $container,
        Logger $loggingService,
        InterfaceFrontendService $frontendService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->container = $container;
        $this->loggingService = $loggingService;
        $this->frontendService = $frontendService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onConfirm',
        ];
    }

    /**
     * Event-function to add the ean item prop
     *
     * @param CheckoutFinishPageLoadedEvent $event
     */

    public function onConfirm(CheckoutFinishPageLoadedEvent $event): void
    {
        $elements = $event->getPage()->getOrder()->getLineItems();
        $reposityheandler = $this->container->get('sales_channel.product.repository');
        $saleschannelContext = $event->getSalesChannelContext();
        $criteria = new Criteria();
        $saleschannel = $saleschannelContext->getSalesChannel();
        $channelId = $saleschannel->getId();
        $languageId = $saleschannel->getLanguageId();
        $systemConfig = $this->systemConfigService->get("SisiSearch.config", $channelId);
        $orRelation = [];
        if (array_key_exists('elasticsearchAktive', $systemConfig)) {
            if ($systemConfig['elasticsearchAktive'] == '1') {
                foreach ($elements as $element) {
                    $orRelation[] = new EqualsFilter('id', $element->getProductId());
                }
                $criteria->addFilter(
                    new MultiFilter(
                        MultiFilter::CONNECTION_OR,
                        $orRelation
                    )
                );
                $entities = $reposityheandler->search($criteria, $saleschannelContext);
                $this->frontendService->delete(
                    $this->systemConfigService,
                    $entities,
                    $this->connection,
                    $channelId,
                    $languageId,
                    $this->loggingService
                );
            }
        }
    }
}
