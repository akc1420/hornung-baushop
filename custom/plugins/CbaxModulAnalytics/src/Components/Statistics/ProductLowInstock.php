<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Cbax\ModulAnalytics\Components\Base;

class ProductLowInstock
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

    public function getProductLowInstock($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = $this->base->getLanguageModifiedContext($context, $languageId);

        $productSearch = $this->base->getProductsForOverviews($parameters['salesChannelIds'], $modifiedContext);

        $data = [];
        foreach ($productSearch as $product)
        {
            if ($product->getChildCount() > 0) continue;

            $productId = $product->getId();
            $productNumber = $product->getProductNumber();
            $stock = $product->getStock();
            $productTransName = $this->base->getProductTranslatedName($product, $productSearch);

            if (!empty($productTransName))
            {
                $data[] = [
                    'id' => $productId,
                    'number' => $productNumber,
                    'name' => $productTransName,
                    'sum' => $stock
                ];
            }
        }

        $data = $this->base->sortArrayByColumn($data, 'sum', 'ASC');

        $seriesData = $this->base->limitData($data, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($data, $this->config['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels']);
        }

        return ['gridData' => $gridData, 'seriesData' => $seriesData];
    }
}

