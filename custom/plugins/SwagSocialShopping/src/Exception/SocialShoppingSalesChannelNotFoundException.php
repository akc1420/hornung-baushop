<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class SocialShoppingSalesChannelNotFoundException extends ShopwareHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'Social Shopping Sales Channel with id "{{ id }}" not found.',
            ['id' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_SOCIAL_SHOPPING__SOCIAL_SHOPPING_SALES_CHANNEL_NOT_FOUND';
    }
}
