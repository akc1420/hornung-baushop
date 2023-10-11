<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation\Validator;

use Shopware\Core\Content\Product\ProductEntity;
use SwagSocialShopping\Component\Validation\NetworkProductValidationResult;
use SwagSocialShopping\Component\Validation\NetworkProductValidatorInterface;
use SwagSocialShopping\Component\Validation\NetworkValidationError;
use SwagSocialShopping\Component\Validation\NetworkValidationErrorCollection;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;

class ProductImageValidator implements NetworkProductValidatorInterface
{
    public function supports(string $network): bool
    {
        return true;
    }

    public function validate(
        ProductEntity $productEntity,
        SocialShoppingSalesChannelEntity $socialShoppingSalesChannelEntity
    ): NetworkProductValidationResult {
        $errors = new NetworkValidationErrorCollection();

        $productMedia = $productEntity->getCover();
        if ($productMedia === null) {
            $errors->add(
                new NetworkValidationError(
                    'swag-social-shopping.validation.product-image.no-image'
                )
            );
        }

        return new NetworkProductValidationResult(
            'product_image',
            $errors
        );
    }
}
