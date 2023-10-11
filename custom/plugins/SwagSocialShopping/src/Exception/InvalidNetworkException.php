<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use SwagSocialShopping\Component\Network\NetworkInterface;

class InvalidNetworkException extends ShopwareHttpException
{
    public function __construct(string $className)
    {
        parent::__construct(
            'Network "{{ network }}" does not implement {{ interface }}.',
            ['network' => $className, 'interface' => NetworkInterface::class]
        );
    }

    public function getErrorCode(): string
    {
        return 'SWAG_SOCIAL_SHOPPING__NETWORK_INVALID';
    }
}
