<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\DataAbstractionLayer\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                                  add(SocialShoppingProductErrorEntity $entity)
 * @method void                                  set(string $key, SocialShoppingProductErrorEntity $entity)
 * @method \Generator                            getIterator()
 * @method SocialShoppingProductErrorEntity[]    getElements()
 * @method SocialShoppingProductErrorEntity|null get(string $key)
 * @method SocialShoppingProductErrorEntity|null first()
 * @method SocialShoppingProductErrorEntity|null last()
 */
class SocialShoppingProductErrorCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SocialShoppingProductErrorEntity::class;
    }
}
