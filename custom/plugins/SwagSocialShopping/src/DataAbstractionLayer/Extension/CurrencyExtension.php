<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Extension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\Currency\CurrencyDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelDefinition;

class CurrencyExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return CurrencyDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'socialShoppingSalesChannels',
                SocialShoppingSalesChannelDefinition::class,
                'currency_id'
            )
        );
    }
}
