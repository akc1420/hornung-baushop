<?php declare(strict_types=1);

namespace Sensus\Check24Connect\ScheduledTask;

use Psr\Log\LoggerInterface;
use Sensus\Check24Connect\Service\FTPService;
use Sensus\Check24Connect\Service\OrderService;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

class ImportTaskHandler extends ScheduledTaskHandler
{

    /**
     * @var FTPService
     */
    private $ftpService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var AbstractSalesChannelContextFactory
     */
    private $salesChannelContextFactory;


    public function __construct(EntityRepositoryInterface $scheduledTaskRepository, FTPService $ftpService,
                                SystemConfigService $systemConfigService, EntityRepositoryInterface $salesChannelRepository,
                                LoggerInterface $logger, OrderService $orderService, AbstractSalesChannelContextFactory $salesChannelContextFactory)
    {
        parent::__construct($scheduledTaskRepository);
        $this->ftpService = $ftpService;
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->logger = $logger;
        $this->orderService = $orderService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }


    public static function getHandledMessages(): iterable
    {
        return [
            Import::class
        ];
    }

    public function run(): void
    {
        $criteria = new Criteria();
        $context = new Context(new SystemSource());
        /** @var SalesChannelEntity $salesChannel */
        foreach ($this->salesChannelRepository->search($criteria, $context) as $salesChannel) {
            if ($this->systemConfigService->get('SensusCheck24Connect.config.active', $salesChannel->getId())) {
                if($this->ftpService->initConfig($salesChannel)) {
                    try {
                        $filename = NULL;
                        while ($filename = $this->ftpService->getNextFile($filename)) {
                            try {
                                $context = $this->salesChannelContextFactory->create(
                                    '',
                                    $salesChannel->getId()
                                );

                                $this->orderService->parseAndExecuteOrder($filename, $context);
                                $this->logger->error($filename);
                            } catch (InvalidCartException $ex) {
                                $this->logger->error('Import-Single ' . $filename . ': ' . $ex->getMessage(), [
                                    'code' => $ex->getCode(),
                                    'trace' => $ex->getTrace(),
                                    'exception' => get_class($ex)
                                ]);

                                foreach($ex->getErrors(true) as $error) {
                                    $this->logger->error('Cart-Exception: ' . $error['detail'], ['details' => $error]);
                                }
                            } catch (\Exception $ex) {
                                $this->logger->error('Import-Single ' . $filename . ': ' . $ex->getMessage(), [
                                    'code' => $ex->getCode(),
                                    'trace' => $ex->getTrace(),
                                    'exception' => get_class($ex)
                                ]);
                            }
                        }
                    } catch (\Exception $ex) {
                        $this->ftpService->close();

                        $this->logger->error('Import-General: ' . $ex->getMessage(), [
                            'code' => $ex->getCode(),
                            'trace' => $ex->getTrace(),
                            'exception' => get_class($ex)
                        ]);
                    }
                }
            }
        }
    }
}