<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToPassFilterException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Trigger\Filter\Filter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\Context;

/**
 * Class SalesChannelFilter
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Filters
 */
class SalesChannelFilter extends Filter
{
    /**
     * Checks if the record and cart data satisfy necessary requirements
     * before the mail can be sent.
     *
     * @param AutomationRecord $record
     * @param Trigger $cartData
     *
     * @throws FailedToPassFilterException
     */
    public function pass(AutomationRecord $record, Trigger $cartData): void
    {
        $salesChannelId = str_replace(AutomationService::STORE_ID_PREFIX, '', $cartData->getCart()->getStoreId());
        $salesChannel = $this->getSalesChannelRepository()
            ->getSalesChannelById($salesChannelId, Context::createDefaultContext());

        if (!$salesChannel) {
            throw new FailedToPassFilterException('Sales channel not found.');
        }

        if (!$salesChannel->getActive()) {
            throw new FailedToPassFilterException('Sales channel is not active.');
        }

        if ($salesChannel->isMaintenance()) {
            throw new FailedToPassFilterException('Sales channel is in maintenance mode.');
        }
    }

    /**
     * @return SalesChannelRepository
     */
    private function getSalesChannelRepository(): SalesChannelRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SalesChannelRepository::class);
    }
}
