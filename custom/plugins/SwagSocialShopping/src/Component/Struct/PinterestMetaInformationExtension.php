<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Struct;

use Shopware\Core\Framework\Struct\Struct;

class PinterestMetaInformationExtension extends Struct
{
    public const AVAILABILITY_IN_STOCK = 'instock';
    public const AVAILABILITY_PREORDER = 'preorder';
    public const AVAILABILITY_BACKORDER = 'backorder';
    public const AVAILABILITY_OUT_OF_STOCK = 'out of stock';

    protected bool $isPinterestSalesChannel;

    protected string $availability;

    public function isPinterestSalesChannel(): bool
    {
        return $this->isPinterestSalesChannel;
    }

    public function setIsPinterestSalesChannel(bool $isPinterestSalesChannel): void
    {
        $this->isPinterestSalesChannel = $isPinterestSalesChannel;
    }

    public function getAvailability(): string
    {
        return $this->availability;
    }

    public function setAvailability(string $availability): void
    {
        $this->availability = $availability;
    }
}
