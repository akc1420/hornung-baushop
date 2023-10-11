<?php

namespace Recommendy\Services\Interfaces;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface SimilarityServiceInterface
{
    /**
     * @param ProductEntity $product
     * @param SalesChannelContext $context
     * @return array
     */
    public function getSimilarArticles(ProductEntity $product, SalesChannelContext $context): array;

    /**
     * @param string $productId
     * @return bool
     */
    public function similarProductsAvailable(string $productId, SalesChannelContext $context): bool;
}
