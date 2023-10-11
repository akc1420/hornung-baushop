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
 * @method void                           add(SocialShoppingOrderEntity $entity)
 * @method void                           set(string $key, SocialShoppingOrderEntity $entity)
 * @method \Generator                     getIterator()
 * @method SocialShoppingOrderEntity[]    getElements()
 * @method SocialShoppingOrderEntity|null get(string $key)
 * @method SocialShoppingOrderEntity|null first()
 * @method SocialShoppingOrderEntity|null last()
 */
class SocialShoppingOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SocialShoppingOrderEntity::class;
    }
}
