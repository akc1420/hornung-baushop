<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Extension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingCustomerDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingOrderDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingProductErrorDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelDefinition;

class SalesChannelExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'socialShoppingSalesChannel',
                'id',
                'sales_channel_id',
                SocialShoppingSalesChannelDefinition::class,
                false
            ))->addFlags(new CascadeDelete())
        );

        $collection->add(
            (new OneToManyAssociationField(
                'socialShoppingProductErrors',
                SocialShoppingProductErrorDefinition::class,
                'sales_channel_id'
            ))->addFlags(new CascadeDelete())
        );

        $collection->add(
            new OneToManyAssociationField(
                'swagSocialShoppingCustomer',
                SocialShoppingCustomerDefinition::class,
                'referral_code'
            )
        );

        $collection->add(
            new OneToManyAssociationField(
                'swagSocialShoppingOrder',
                SocialShoppingOrderDefinition::class,
                'referral_code'
            )
        );
    }
}
