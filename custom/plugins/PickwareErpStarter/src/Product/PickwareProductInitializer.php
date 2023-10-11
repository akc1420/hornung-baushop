<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Product;

use Doctrine\DBAL\Connection;
use Pickware\DalBundle\Sql\SqlUuid;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PickwareProductInitializer implements EventSubscriberInterface
{
    private Connection $db;
    private ?EventDispatcherInterface $eventDispatcher;

    /**
     * @deprecated next-major `?EventDispatcherInterface $eventDispatcher` will be non-optional
     */
    public function __construct(Connection $db, ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->db = $db;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'productWritten',
        ];
    }

    public function productWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        if ($entityWrittenEvent->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $productIds = [];
        foreach ($entityWrittenEvent->getWriteResults() as $writeResult) {
            // $writeResult->getExistence() can be null, but we have no idea why and also not what this means.
            $existence = $writeResult->getExistence();
            if (($existence === null && $writeResult->getOperation() === EntityWriteResult::OPERATION_INSERT)
                || ($existence !== null && !$existence->exists())
            ) {
                $productIds[] = $writeResult->getPrimaryKey();
            }
        }

        $this->ensurePickwareProductsExist($productIds, true);
    }

    public function ensurePickwareProductsExist(array $productIds, ?bool $productInserted = false): void
    {
        if (!$this->eventDispatcher) {
            return;
        }
        if (count($productIds) === 0) {
            return;
        }

        $this->db->executeStatement(
            'INSERT INTO `pickware_erp_pickware_product` (
                id,
                product_id,
                product_version_id,
                reserved_stock,
                stock_not_available_for_sale,
                incoming_stock,
                is_stock_management_disabled,
                created_at
            ) SELECT
                ' . SqlUuid::UUID_V4_GENERATION . ',
                product.id,
                product.version_id,
                0,
                0,
                0,
                0,
                NOW(3)
            FROM `product`
            WHERE `product`.`id` IN (:ids) AND `product`.`version_id` = :liveVersionId
            ON DUPLICATE KEY UPDATE `pickware_erp_pickware_product`.`id` = `pickware_erp_pickware_product`.`id`',
            [
                'ids' => array_map('hex2bin', $productIds),
                'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
            ],
            [
                'ids' => Connection::PARAM_STR_ARRAY,
            ],
        );

        if ($productInserted) {
            // If a product gets cloned, the pickwareProduct extension gets cloned as well. We want that for certain
            // values of the pickwareProduct, e.g. reorderPoint or isStockManagementDisabled. Other values are
            // set to their default values by executing this query.
            $this->db->executeStatement(
                'UPDATE `pickware_erp_pickware_product`
            SET `pickware_erp_pickware_product`.`reserved_stock` = 0,
                `pickware_erp_pickware_product`.`incoming_stock` = 0,
                `pickware_erp_pickware_product`.`stock_not_available_for_sale` = 0
            WHERE `pickware_erp_pickware_product`.`product_id` IN (:productIds)
            AND `pickware_erp_pickware_product`.`product_version_id` = :liveVersionId;',
                [
                    'productIds' => array_map('hex2bin', $productIds),
                    'liveVersionId' => hex2bin(Defaults::LIVE_VERSION),
                ],
                [
                    'productIds' => Connection::PARAM_STR_ARRAY,
                ],
            );

            $this->eventDispatcher->dispatch(new PickwareProductInsertedEvent($productIds));
        }
    }

    public function ensurePickwareProductsExistForAllProducts(): void
    {
        $this->db->executeStatement(
            'INSERT INTO `pickware_erp_pickware_product` (
                id,
                product_id,
                product_version_id,
                reserved_stock,
                stock_not_available_for_sale,
                incoming_stock,
                is_stock_management_disabled,
                created_at
            ) SELECT
                ' . SqlUuid::UUID_V4_GENERATION . ',
                product.id,
                product.version_id,
                0,
                0,
                0,
                0,
                NOW(3)
            FROM `product`
            WHERE `product`.`version_id` = :liveVersionId
            ON DUPLICATE KEY UPDATE `pickware_erp_pickware_product`.`id` = `pickware_erp_pickware_product`.`id`',
            ['liveVersionId' => hex2bin(Defaults::LIVE_VERSION)],
        );
    }
}
