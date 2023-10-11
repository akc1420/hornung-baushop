<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToCreateCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\CartAutomationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

/**
 * Class AutomationService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation
 */
class AutomationService
{
    public const STORE_ID_PREFIX = 'Shopware 6 - ';
    public const THEA_TITLE_PREFIX = 'Shopware 6 - Abandoned cart - ';

    /**
     * @var CartAutomationService
     */
    private $cartService;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var AutomationRecordService
     */
    private $automationRecordService;

    /**
     * AutomationService constructor.
     *
     * @param CartAutomationService $cartService
     * @param SalesChannelRepository $salesChannelRepository
     * @param AutomationRecordService $automationRecordService
     */
    public function __construct(
        CartAutomationService $cartService,
        SalesChannelRepository $salesChannelRepository,
        AutomationRecordService $automationRecordService
    ) {
        $this->cartService = $cartService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->automationRecordService = $automationRecordService;
    }

    /**
     * Provides cart automation identified by the id.
     *
     * @param string $storeId
     *
     * @return CartAutomation|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function get(string $storeId): ?CartAutomation
    {
        $storeId = self::STORE_ID_PREFIX . $storeId;
        $result = $this->cartService->findBy(['storeId' => $storeId]);

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Provides active shops.
     *
     * @return EntitySearchResult
     */
    public function getActiveShops(): EntitySearchResult
    {
        return $this->salesChannelRepository->getActiveSalesChannels(Context::createDefaultContext());
    }

    /**
     * Creates cart automation.
     *
     * @param string $storeId
     *
     * @return CartAutomation
     *
     * @throws FailedToCreateCartException
     * @throws FailedToDeleteAutomationRecordException
     * @throws FailedToDeleteCartException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function create(string $storeId): CartAutomation
    {
        $salesChannel = $this->salesChannelRepository->getSalesChannelById(
            $storeId,
            Context::createDefaultContext()
        );

        if ($salesChannel === null) {
            throw new FailedToCreateCartException('Sales channel not found.');
        }

        $cart = $this->cartService->findBy(['storeId' => self::STORE_ID_PREFIX . $storeId]);

        if (!empty($cart[0])) {
            $this->doDelete($cart[0]->getId());
        }

        $cart = $this->cartService->create(
            self::STORE_ID_PREFIX . $storeId,
            self::THEA_TITLE_PREFIX . $salesChannel->getName(),
            'Shopware 6',
            ['delay' => 10]
        );

        return $cart;
    }

    /**
     * Deletes automation for given shop.
     *
     * @param string $storeId
     *
     * @throws FailedToDeleteAutomationRecordException
     * @throws FailedToDeleteCartException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function delete(string $storeId): void
    {
        $cart = $this->cartService->findBy(['storeId' => self::STORE_ID_PREFIX . $storeId]);

        if (empty($cart[0])) {
            return;
        }

        $this->doDelete($cart[0]->getId());
    }

    /**
     * Deletes cart records.
     *
     * @param string $storeId
     *
     * @throws FailedToDeleteAutomationRecordException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function deleteRecords(string $storeId): void
    {
        $cart = $this->cartService->findBy(['storeId' => self::STORE_ID_PREFIX . $storeId]);

        if (empty($cart[0])) {
            return;
        }

        $this->automationRecordService->deleteBy(['automationId' => $cart[0]->getId()]);
    }

    /**
     * Updates cart delay.
     *
     * @param string $storeId
     * @param string $delay
     *
     * @return CartAutomation
     *
     * @throws FailedToCreateCartException
     * @throws FailedToUpdateCartException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function updateDelay(string $storeId, string $delay): CartAutomation
    {
        $salesChannel = $this->salesChannelRepository->getSalesChannelById(
            $storeId,
            Context::createDefaultContext()
        );

        if ($salesChannel === null) {
            throw new FailedToCreateCartException('Sales channel not found.');
        }

        $cart = $this->cartService->findBy(['storeId' => self::STORE_ID_PREFIX . $storeId]);

        if (empty($cart[0])) {
            throw new FailedToUpdateCartException('Cart not found for given store.');
        }

        $cart = $cart[0];
        $cart->setSettings(['delay' => $delay]);
        $this->cartService->update($cart);

        return $cart;
    }

    /**
     * Deletes cart automation.
     *
     * @param $id
     *
     * @throws FailedToDeleteAutomationRecordException
     * @throws FailedToDeleteCartException
     */
    private function doDelete($id): void
    {
        $this->automationRecordService->deleteBy([
            'automationId' => $id
        ]);
        $this->cartService->delete($id);
    }
}
