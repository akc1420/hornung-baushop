<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\GoodsReceipt\Model;

use Pickware\PickwareErpStarter\GoodsReceipt\GoodsReceiptStateMachine;
use Pickware\PickwareErpStarter\Stock\Model\StockDefinition;
use Pickware\PickwareErpStarter\Stock\Model\StockMovementDefinition;
use Pickware\PickwareErpStarter\Warehouse\Model\WarehouseDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CashRoundingConfigField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StateMachineStateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\User\UserDefinition;

class GoodsReceiptDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'pickware_erp_goods_receipt';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return GoodsReceiptEntity::class;
    }

    public function getCollectionClass(): string
    {
        return GoodsReceiptCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            new FkField('currency_id', 'currencyId', CurrencyDefinition::class, 'id'),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id'),
            new FloatField('currency_factor', 'currencyFactor'),
            new CashRoundingConfigField('item_rounding', 'itemRounding'),
            new CashRoundingConfigField('total_rounding', 'totalRounding'),

            new CartPriceField('price', 'price'),
            (new FloatField('amount_total', 'amountTotal'))->addFlags(new Computed(), new WriteProtected()),
            (new FloatField('amount_net', 'amountNet'))->addFlags(new Computed(), new WriteProtected()),
            (new FloatField('position_price', 'positionPrice'))->addFlags(new Computed(), new WriteProtected()),
            (new StringField('tax_status', 'taxStatus'))->addFlags(new Computed(), new WriteProtected()),

            new FkField('user_id', 'userId', UserDefinition::class, 'id'),
            new ManyToOneAssociationField('user', 'user_id', UserDefinition::class, 'id'),
            (new JsonField('user_snapshot', 'userSnapshot'))->addFlags(new Required()),
            new FkField('warehouse_id', 'warehouseId', WarehouseDefinition::class, 'id'),
            new ManyToOneAssociationField('warehouse', 'warehouse_id', WarehouseDefinition::class, 'id'),
            (new JsonField('warehouse_snapshot', 'warehouseSnapshot'))->addFlags(new Required()),

            (new NumberRangeField('number', 'number'))->addFlags(new Required()),
            new LongTextField('comment', 'comment'),
            (new StateMachineStateField(
                'state_id',
                'stateId',
                GoodsReceiptStateMachine::TECHNICAL_NAME,
            ))->addFlags(new Required()),
            (new ManyToOneAssociationField(
                'state',
                'state_id',
                StateMachineStateDefinition::class,
                'id',
            ))->addFlags(new ApiAware()),

            // Reverse side associations
            (new OneToManyAssociationField(
                'items',
                GoodsReceiptItemDefinition::class,
                'goods_receipt_id',
                'id',
            ))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField(
                'sourceStockMovements',
                StockMovementDefinition::class,
                'source_goods_receipt_id',
                'id',
            )),
            (new OneToManyAssociationField(
                'destinationStockMovements',
                StockMovementDefinition::class,
                'destination_goods_receipt_id',
                'id',
            )),
            (new OneToManyAssociationField(
                'stocks',
                StockDefinition::class,
                'goods_receipt_id',
                'id',
            ))->addFlags(new RestrictDelete()),
        ]);
    }
}
