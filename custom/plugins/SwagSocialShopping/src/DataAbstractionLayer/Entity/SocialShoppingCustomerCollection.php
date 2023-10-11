<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                              add(SocialShoppingCustomerEntity $entity)
 * @method void                              set(string $key, SocialShoppingCustomerEntity $entity)
 * @method \Generator                        getIterator()
 * @method SocialShoppingCustomerEntity[]    getElements()
 * @method SocialShoppingCustomerEntity|null get(string $key)
 * @method SocialShoppingCustomerEntity|null first()
 * @method SocialShoppingCustomerEntity|null last()
 */
class SocialShoppingCustomerCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SocialShoppingCustomerEntity::class;
    }
}
