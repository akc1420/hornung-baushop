<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation;

use Shopware\Core\Content\Product\ProductEntity;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;

interface NetworkProductValidatorInterface
{
    public function supports(string $network): bool;

    public function validate(
        ProductEntity $productEntity,
        SocialShoppingSalesChannelEntity $socialShoppingSalesChannelEntity
    ): NetworkProductValidationResult;
}
