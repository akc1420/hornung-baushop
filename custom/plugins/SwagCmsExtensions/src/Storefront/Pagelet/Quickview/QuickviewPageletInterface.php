<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Storefront\Pagelet\Quickview;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Storefront\Page\Product\Review\ReviewLoaderResult;

interface QuickviewPageletInterface
{
    public function getProduct(): ProductEntity;

    public function getReviews(): ReviewLoaderResult;

    public function getTotalReviews(): int;

    public function getConfiguratorSettings(): PropertyGroupCollection;
}
