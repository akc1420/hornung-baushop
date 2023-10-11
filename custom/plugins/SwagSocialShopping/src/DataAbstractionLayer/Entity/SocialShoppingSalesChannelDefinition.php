<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Entity;

use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SocialShoppingSalesChannelDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_social_shopping_sales_channel';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return SocialShoppingSalesChannelEntity::class;
    }

    public function getCollectionClass(): string
    {
        return SocialShoppingSalesChannelCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new FkField('sales_channel_domain_id', 'salesChannelDomainId', SalesChannelDomainDefinition::class))->addFlags(new Required()),
            new FkField('product_stream_id', 'productStreamId', ProductStreamDefinition::class),
            new FkField('currency_id', 'currencyId', CurrencyDefinition::class),

            (new StringField('network', 'network'))->addFlags(new Required()),
            new JsonField('configuration', 'configuration'),
            new BoolField('is_validating', 'isValidating'),
            new DateTimeField('last_validation', 'lastValidation'),

            (new OneToOneAssociationField('salesChannel', 'sales_channel_id', 'id', SalesChannelDefinition::class, false))->addFlags(new RestrictDelete()),
            new ManyToOneAssociationField('productStream', 'product_stream_id', ProductStreamDefinition::class),
            new ManyToOneAssociationField('salesChannelDomain', 'sales_channel_domain_id', SalesChannelDomainDefinition::class),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class),
        ]);
    }
}
