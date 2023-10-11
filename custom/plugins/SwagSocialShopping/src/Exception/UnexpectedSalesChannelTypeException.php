<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class UnexpectedSalesChannelTypeException extends ShopwareHttpException
{
    public function __construct(string $typeId, int $pregErrorCode)
    {
        parent::__construct(
            'Unexpected Sales Channel type id given ({{ typeId }}). Check your type id and preg settings. Preg error code: {{ pregErrorCode }}',
            ['typeId' => $typeId, 'pregErrorCode' => $pregErrorCode]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_SOCIAL_SHOPPING__UNEXPECTED_SALES_CHANNEL_TYPE';
    }
}
