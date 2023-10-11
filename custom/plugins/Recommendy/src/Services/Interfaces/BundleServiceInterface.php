<?php

namespace Recommendy\Services\Interfaces;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface BundleServiceInterface
{
    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getBundleArticles(ProductEntity $product, SalesChannelContext $context): array;

    /**
     * @param array $basketProductIds
     * @param SalesChannelContext $context
     * @return array
     */
    public function getCartBundleArticles(array $basketProductIds, SalesChannelContext $context): array;
}
