<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Service;

use Ott\IdealoConnector\Component\IdealoClientException;
use Ott\IdealoConnector\Dbal\DataProvider;
use Ott\IdealoConnector\Gateway\OrderStatusGateway;
use Ott\IdealoConnector\OttIdealoConnector;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CronjobService
{
    private ConfigProvider $configProvider;
    private ClientService $clientService;
    private OrderImportService $importService;
    private OrderExportService $exportService;
    private LoggerInterface $logger;
    private DataProvider $dataProvider;
    private OrderStatusGateway $orderStatusGateway;

    public function __construct(
        ConfigProvider $configProvider,
        ClientService $clientService,
        OrderImportService $importService,
        OrderExportService $exportService,
        DataProvider $dataProvider,
        OrderStatusGateway $orderStatusGateway,
        LoggerInterface $logger
    )
    {
        $this->configProvider = $configProvider;
        $this->clientService = $clientService;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->logger = $logger;
        $this->dataProvider = $dataProvider;
        $this->orderStatusGateway = $orderStatusGateway;
    }

    public function importOrdersFromIdealo(bool $isScheduledTask = false): array
    {
        $salesChannels = $this->dataProvider->getSalesChannels();
        $result = [];

        if ($this->configProvider->isDeactivateScheduledTask() && $isScheduledTask) {
            return $result;
        }

        foreach ($salesChannels as $salesChannel) {
            /* @var SalesChannelEntity $salesChannel */
            $this->configProvider->init($salesChannel->getId());

            if (empty($this->configProvider->getClientId())) {
                $result[$salesChannel->getId()] = sprintf(IdealoClientException::ERROR_PATTERN_NO_API_KEY, $salesChannel->getId());
                continue;
            }

            try {
                $orders = $this->clientService->getOrders();
                $orders = array_merge($orders, $this->clientService->getRevokedOrders());
            } catch (IdealoClientException $e) {
                $result[$salesChannel->getId()] = sprintf('[%s] %s', $salesChannel->getName(), $e->getMessage());
                continue;
            }

            $importedOrders = 0;
            if (\is_array($orders)) {
                $this->importService->setConfigProvider($this->configProvider);
                foreach ($orders as $orderItem) {
                    try {
                        if (!\is_array($orderItem)) {
                            $result[$salesChannel->getId()] = $orderItem;
                            continue 2;
                        }

                        $checkOrder = $this->dataProvider->isIdealoOrder($orderItem['idealoOrderId']);
                        if (!$checkOrder) {
                            $orderNumber = $this->importService->importOrder($orderItem, $salesChannel);
                            $this->clientService->sendOrderNumber($orderItem['idealoOrderId'], $orderNumber);
                            ++$importedOrders;
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical(sprintf(
                            '[%s][%s][%s] %s',
                            $e->getFile(),
                            $e->getLine(),
                            $e->getCode(),
                            $e->getMessage()
                        ));
                    }
                }
            }

            $result[$salesChannel->getId()] = sprintf(
                'import %s orders from idealo (salesChannel %s)',
                $importedOrders,
                $salesChannel->getName()
            );
        }

        return $result;
    }

    public function transferOrderStatesToIdealo(bool $isScheduledTask = false): array
    {
        $salesChannels = $this->dataProvider->getSalesChannels();
        $result = [];

        if ($this->configProvider->isDeactivateScheduledTask() && $isScheduledTask) {
            return $result;
        }

        foreach ($salesChannels as $salesChannel) {
            /* @var SalesChannelEntity $salesChannel */
            $this->configProvider->init($salesChannel->getId());

            if (empty($this->configProvider->getClientId())) {
                $result[$salesChannel->getId()] = sprintf(IdealoClientException::ERROR_PATTERN_NO_API_KEY, $salesChannel->getName());
                continue;
            }

            $transmittedOrderStates = 0;
            $ordersForShippingTransfer = $this->orderStatusGateway->getOrdersForStateTransmission($this->configProvider->getOrderCompletionState(), $salesChannel->getId());
            if (0 < \count($ordersForShippingTransfer)) {
                foreach ($ordersForShippingTransfer as $orderForShippingTransfer) {
                    try {
                        $order = $this->dataProvider->getOrderById(strtolower($orderForShippingTransfer['id']));
                        $trackingCodes = $order->getDeliveries()->first()->getTrackingCodes();
                        $trackingCode = \is_array($trackingCodes) && !empty($trackingCodes) ? $trackingCodes[0] : '';

                        $carrier = $this->exportService->getCarrier(
                            $trackingCode,
                            $this->configProvider->getCarrier(),
                            $this->configProvider->getCustomCarrierRule()
                        );

                        $this->clientService->fulfillOrder(
                            $orderForShippingTransfer['idealo_transaction_id'],
                            $carrier,
                            $trackingCode
                        );

                        $this->exportService->storeLineItemsFulfillment(strtolower($orderForShippingTransfer['idealo_order_id']), $order->getLineItems());
                        ++$transmittedOrderStates;
                    } catch (IdealoClientException $e) {
                        $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
                    }
                }
            }

            $ordersForCancelTransfer = $this->orderStatusGateway->getOrdersForStateTransmission($this->configProvider->getOrderCancellationState(), $salesChannel->getId(), true);
            if (0 < \count($ordersForCancelTransfer)) {
                foreach ($ordersForCancelTransfer as $orderForCancelTransfer) {
                    try {
                        $order = $this->dataProvider->getOrderById(strtolower($orderForCancelTransfer['id']));
                        $this->clientService->cancelOrder(
                            $orderForCancelTransfer['idealo_transaction_id'],
                            $order->getLineItems()
                        );

                        if (\in_array(OttIdealoConnector::TITLE_IDEALO_PAYMENT, $order->getTransactions()->getPaymentMethodIds())) {
                            $this->clientService->refundOrder(
                                $orderForCancelTransfer['idealo_transaction_id'],
                                $order->getAmountTotal()
                            );
                        }

                        $this->exportService->storeLineItemsCancellation(strtolower($orderForCancelTransfer['idealo_order_id']), $order->getLineItems());
                        ++$transmittedOrderStates;
                    } catch (IdealoClientException $e) {
                        $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
                    }
                }
            }

            $result[$salesChannel->getId()] = sprintf(
                'transmitted %s of %s open orders to idealo (salesChannel %s)',
                $transmittedOrderStates,
                \count($ordersForShippingTransfer) + \count($ordersForCancelTransfer),
                $salesChannel->getName()
            );
        }

        return $result;
    }
}
