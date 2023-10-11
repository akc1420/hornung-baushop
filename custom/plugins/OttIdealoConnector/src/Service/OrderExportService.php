<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Service;

use Ott\IdealoConnector\Dbal\DataPersister;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;

class OrderExportService
{
    private const LINE_ITEM_STATUS_SEND = 'send';
    private const LINE_ITEM_STATUS_CANCEL = 'cancel';
    private DataPersister $dataPersister;

    public function __construct(DataPersister $dataPersister)
    {
        $this->dataPersister = $dataPersister;
    }

    public function storeLineItemsFulfillment(string $idealoOrderId, ?OrderLineItemCollection $lineItems): void
    {
        $this->storeLineItemsStatus($idealoOrderId, $lineItems, self::LINE_ITEM_STATUS_SEND);
    }

    public function storeLineItemsCancellation(string $idealoOrderId, ?OrderLineItemCollection $lineItems): void
    {
        $this->storeLineItemsStatus($idealoOrderId, $lineItems, self::LINE_ITEM_STATUS_CANCEL);
    }

    private function storeLineItemsStatus(string $idealoOrderId, ?OrderLineItemCollection $lineItems, string $status): void
    {
        foreach ($lineItems as $lineItem) {
            $this->dataPersister->createLineItemStatus($idealoOrderId, $lineItem->getId(), $status);
        }
    }

    public function getCarrier(string &$trackingCode, string $defaultCarrier, string $carrierRule): string
    {
        $carrier = '';
        if (!empty($carrierRule)) {
            $rules = explode('|', $carrierRule);
            foreach ($rules as $rule) {
                [$carrier, $pattern, $replace] = explode('=', $rule, 3);

                if (null !== $pattern && preg_match($pattern, $trackingCode)) {
                    $trackingCode = preg_replace($replace, '', $trackingCode);
                    break;
                }
                $carrier = '';
            }
        }

        if (empty($carrier)) {
            $carrier = str_replace(' ', '', $defaultCarrier);
        }

        return $carrier;
    }
}
