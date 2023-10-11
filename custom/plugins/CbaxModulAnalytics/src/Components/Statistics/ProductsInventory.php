<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Cbax\ModulAnalytics\Components\Base;

class ProductsInventory
{
    private $config;
    private $base;

    public function __construct(
        $config,
        Base $base
    )
    {
        $this->config = $config;
        $this->base = $base;
    }

    public function getProductsInventory($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $productSearch = $this->base->getProductsForOverviews($parameters['salesChannelIds'], $modifiedContext);

        $data = [];
        foreach ($productSearch as $product) {
            if (empty($product->getActive())) continue;
            if ($product->getChildCount() > 0) continue;
            $product = $this->base->checkParentPurchasePrice($product, $productSearch);
            $productId = $product->getId();
            $productNumber = $product->getProductNumber();
            $stock = $product->getStock();

            $purchasePrice = $this->base->calculatePurchasePrice($product, $productSearch, $context);
            $purchasePrice = empty($purchasePrice) ? $purchasePrice : round((float)$purchasePrice, 2);
            $worth = empty($purchasePrice) ? null : $purchasePrice * (float)$stock;

            $productTransName = $this->base->getProductTranslatedName($product, $productSearch);

            if (!empty($productTransName)) {
                $data[] = [
                    'id' => $productId,
                    'number' => $productNumber,
                    'name' => $productTransName,
                    'sum' => $stock,
                    'pprice' => $purchasePrice,
                    'worth' => $worth
                ];
            }
        }

        $overall = array_sum(array_column($data, 'worth'));
        $sortingField = !empty($parameters['sorting'][0]) ? $parameters['sorting'][0] : 'worth';
        $direction = !empty($parameters['sorting'][1]) ? $parameters['sorting'][1] : 'DESC';
        $data = $this->base->sortArrayByColumn($data, $sortingField, $direction);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $data, 'overall' => $overall];
    }
}


