<?php declare(strict_types=1);

namespace Dtgs\GoogleTagManager\Services;

use Dtgs\GoogleTagManager\Components\Helper\CategoryHelper;
use Dtgs\GoogleTagManager\Components\Helper\LoggingHelper;
use Dtgs\GoogleTagManager\Components\Helper\ManufacturerHelper;
use Dtgs\GoogleTagManager\Components\Helper\PriceHelper;
use Dtgs\GoogleTagManager\Components\Helper\ProductHelper;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Ga4Service
{
    private $systemConfigService;
    /**
     * @var ContainerInterface $container
     */
    private $container;
    private $priceHelper;
    private $loggingHelper;
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
    /**
     * @var CategoryHelper
     */
    private $categoryHelper;
    /**
     * @var ManufacturerHelper
     */
    private $manufacturerHelper;

    public function __construct(SystemConfigService $systemConfigService,
                                ContainerInterface $container,
                                ProductHelper $productHelper,
                                CategoryHelper $categoryHelper,
                                ManufacturerHelper $manufacturerHelper,
                                PriceHelper $priceHelper,
                                LoggingHelper $loggingHelper)
    {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->productHelper = $productHelper;
        $this->categoryHelper = $categoryHelper;
        $this->manufacturerHelper = $manufacturerHelper;
        $this->priceHelper = $priceHelper;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * Maybe move to general helper
     *
     * Helper to get plugin specific config
     *
     * @return array|mixed|null
     */
    public function getGtmConfig($salesChannelId) {
        return $tagManagerConfig = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config', $salesChannelId);
    }

    /**
     * @param $ga4Tags
     * @return false|string
     */
    public function prepareTagsForView($ga4Tags)
    {
        return json_encode($ga4Tags);
    }

    /**
     * since 6.0.5
     *
     * @param array $tags
     * @param string $event_name
     * @return array
     */
    private function addEeEvent($tags, $event_name = '')
    {
        $event_array = [];
        if($event_name != '') $event_array = ['event' => $event_name];

        return array_merge(
            $event_array,
            ['ecommerce' => $tags]
        );
    }

    /**
     * @param SalesChannelProductEntity $product
     * @param SalesChannelContext $context
     * @return mixed
     * @throws \Exception
     */
    public function getDetailTags(SalesChannelProductEntity $product, SalesChannelContext $context) {

        $ga4_tags = [];
        //Currency Code
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();

        //New in 1.3.5 - select if brutto/netto
        $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
        $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

        $taxRate = $product->getCalculatedPrice()->getTaxRules()->first();
        if($taxRate) {
            $tax = $taxRate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $tax = 0;
        }

        $item_price = (float) $this->priceHelper->getPrice($brutto_price, $tax);

        $product_data = [
            'item_name'      =>  $product->getTranslation('name'),
            'item_id'        =>  $product->getProductNumber(),
            'price'          =>  $item_price,
            'index' => 0, //Index auf Detailseiten immer 0
            'item_list_name' => 'Category',
            'quantity' => 1,
        ];

        //Product Category - Changed to SEO Category in V6.1.22
        $seoCategory = $product->getSeoCategory();
        if($seoCategory) {
            $product_data['item_category'] = $seoCategory->getTranslation('name');
            $product_data['item_list_id'] = $seoCategory->getId();
        }

        if($product->getManufacturer())
            $product_data['item_brand'] = $product->getManufacturer()->getTranslation('name');

        $ga4_tags['value'] = $item_price;
        $ga4_tags['items'] = [$product_data];

        /**
         * Related und Similar Articles: tbd
         */

        return $this->addEeEvent($ga4_tags, 'view_item');

    }

    /**
     * SW6 ready
     *
     * @param $navigationId
     * @param EntitySearchResult $result
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getNavigationTags($navigationId, $listing, SalesChannelContext $context) {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $eeMaxAmountCategoriesForImpressions = (isset($pluginConfig['eeMaxAmountCategoriesForImpressions'])) ? $pluginConfig['eeMaxAmountCategoriesForImpressions'] : 0;

        $ga4_tags = [];
        //Currency Code
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();

        $category = $this->categoryHelper->getCategoryById($navigationId, $context);

        //Impressions
        $ga4_tags['items'] = $this->getImpressions($listing, $eeMaxAmountCategoriesForImpressions, 'Category', $category->getTranslation('name'));

        return $this->addEeEvent($ga4_tags, 'view_item_list');

    }

    /**
     * SW6 ready
     *
     * @param $cartOrOrder
     * @param $event
     * @return array
     * @throws \Exception
     */
    public function getCheckoutTags($cartOrOrder, $event) {

        $pluginConfig = $this->getGtmConfig($event->getSalesChannelContext()->getSalesChannel()->getId());
        $addCategoryNames = (isset($pluginConfig['eeAddCategorynameInCheckout'])) ? $pluginConfig['eeAddCategorynameInCheckout'] : false;
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        $ga4_tags = [];
        //Currency Code
        $ga4_tags['currency'] = $event->getSalesChannelContext()->getCurrency()->getIsoCode();
        $ga4_tags['value'] = ($useNetPrices) ? $cartOrOrder->getPrice()->getNetPrice() : $cartOrOrder->getPrice()->getTotalPrice();
        $ga4_tags['items'] = $this->getBasketItems($cartOrOrder->getLineItems(), $event->getSalesChannelContext(), $addCategoryNames, 'checkout');

        return $this->addEeEvent($ga4_tags, $this->getCheckoutEventName($event));

    }

    /**
     * SW6 ready
     *
     * @param OrderEntity $order
     * @param SalesChannelContext $context
     * @return array
     * @throws \Exception
     */
    public function getPurchaseConfirmationTags(OrderEntity $order, SalesChannelContext $context): array
    {

        $pluginConfig = $this->getGtmConfig($context->getSalesChannel()->getId());
        $addCategoryNames = (isset($pluginConfig['eeAddCategorynameInCheckout'])) ? $pluginConfig['eeAddCategorynameInCheckout'] : false;
        $useNetPrices = isset($pluginConfig['showPriceType']) && $pluginConfig['showPriceType'] == 'netto';

        //added in 6.1.24
        $shipping = $order->getShippingCosts();
        if($shipping instanceof CalculatedPrice) $shipping_tax_rate = $shipping->getTaxRules()->first();
        else $shipping_tax_rate = null;

        if($shipping_tax_rate) {
            $shippingTax = $shipping_tax_rate->getTaxRate();
        }
        else {
            //Bugfix for tax free countries, V6.1.4
            $shippingTax = 0;
        }

        $lineItems = $order->getLineItems()->filterByType(PromotionProcessor::LINE_ITEM_TYPE);
        if ($lineItems->count() >= 1) {
            $promoCode = $this->getPromotionCode($order->getLineItems());
            if($promoCode != '') {
                $actionField['coupon'] = $promoCode;
            }
        }

        //added in 6.1.24
        $revenue = ($useNetPrices) ? $order->getAmountNet() : $order->getAmountTotal();

        //Finish fields
        $ga4_tags = [];
        $ga4_tags['currency'] = $context->getCurrency()->getIsoCode();
        $ga4_tags['transaction_id'] = $order->getOrderNumber();
        $ga4_tags['value'] = $revenue;
        $ga4_tags['tax'] = $this->priceHelper->formatPrice($order->getAmountTotal() - $order->getAmountNet());
        $ga4_tags['shipping'] = (float) $this->priceHelper->getPrice($order->getShippingTotal(), $shippingTax);
        $ga4_tags['items'] = $this->getBasketItems($order->getLineItems(), $context, $addCategoryNames, 'purchase');

        return $this->addEeEvent($ga4_tags, 'purchase');

    }

    /**
     * @param $listing
     * @param int $maxCategories
     * @param string $listName
     * @param string $category
     * @return array
     * @throws \Exception
     */
    private function getImpressions($listing, int $maxCategories, string $listName = 'Search', string $category = ''): array
    {

        $tags = array();
        if(empty($listing)) return $tags;

        $i = 0;

        foreach($listing as $product) {
            /** @var SalesChannelProductEntity $product */
            $price = ($product->getCalculatedPrices()->count()) ? $product->getCalculatedPrices()->first()->getUnitPrice() : $product->getCalculatedPrice()->getUnitPrice();
            $brutto_price = (is_float($price)) ? $price : str_replace(',', '.', $price);

            $taxRate = $product->getCalculatedPrice()->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.4
                $tax = 0;
            }

            $item = array(
                'item_name'      =>  $product->getTranslation('name'),
                'item_id'        =>  $product->getProductNumber(),
                'price'          =>  (float) $this->priceHelper->getPrice($brutto_price, $tax),
                'item_brand'     =>  ($product->getManufacturer() !== null) ? $product->getManufacturer()->getTranslation('name') : '',
                'index'          =>  ++$i,
                'quantity'       => 1
            );
            if($listName) $item['item_list_name'] = $listName;
            if($category) $item['item_list_id'] = $category;
            $tags[] = $item;

            //since 6.1.35
            if($maxCategories > 0 && $i >= $maxCategories) break;
        }

        return $tags;

    }

    /**
     * @param $listing
     * @param SalesChannelContext $context
     * @param bool $addCategoryNames
     * @param string $location
     * @return array
     * @throws \Exception
     */
    private function  getBasketItems($listing, SalesChannelContext $context, $addCategoryNames = false, $location = 'checkout'): array
    {

        $tags = array();
        if(empty($listing)) return $tags;

        foreach($listing as $product) {
            /** @var LineItem $product */
            $taxRate = $product->getPrice()->getTaxRules()->first();
            if($taxRate) {
                $tax = $taxRate->getTaxRate();
            }
            else {
                //Bugfix for tax free countries, V6.1.4
                $tax = 0;
            }

            $payload = $product->getPayload();

            $productNumber = (isset($payload['productNumber'])) ? $payload['productNumber'] : 'voucher';
            $productName = $product->getLabel();

            //Anpassung für Swag Custom Products - V6.1.29
            if($product->getType() == 'customized-products' && $product->getChildren()) {
                foreach($product->getChildren() as $child) {
                    if($child->getType() == 'product') {
                        $productName = $child->getLabel();
                        $childPayload = $child->getPayload();
                        $productNumber = (isset($childPayload['productNumber'])) ? $childPayload['productNumber'] : 'customized-product';
                    }
                }
            }
            if($product->getType() == 'customized-products-option') {
                $productNumber = 'customized-product-option-' . strtolower($product->getLabel());
            }
            if(($product->getType() == 'option-values' || $product->getType() == 'customized-products') && $location === 'purchase') {
                continue;
            }

            $item = array(
                'item_name'      =>  $productName,
                'item_id'        =>  $productNumber,
                'price'     =>  (float) $this->priceHelper->getPrice($product->getPrice()->getUnitPrice(), $tax),
                'quantity'  =>  $product->getQuantity(),
            );

            if(isset($payload['manufacturerId'])) {
                //V6.1.20: add manufacturer-name
                $manufacturer = $this->manufacturerHelper->getManufacturerById($payload['manufacturerId'], $context);
                if($manufacturer !== null) {
                    $item['item_brand'] = $manufacturer->getTranslation('name');
                }
            }

            //Product Category - Changed to SEO Category in V6.1.22
            if($addCategoryNames) {
                if($product->getReferencedId()) {
                    $salesChannelProduct = $this->productHelper->getSalesChannelProductEntityByProductId($product->getReferencedId(), $context);
                    if($salesChannelProduct !== null && $salesChannelProduct->getSeoCategory() !== null) {
                        $item['item_category'] = $salesChannelProduct->getSeoCategory()->getTranslation('name');
                    }
                }
            }

            $tags[] = $item;
        }

        return $tags;

    }

    /**
     * @param $event
     * @return int
     */
    private function getCheckoutEventName($event)
    {

        $event_name = 0;

        switch (get_class($event)) {
            case CheckoutConfirmPageLoadedEvent::class:
                $event_name = 'confirm_order';
                break;
            case CheckoutRegisterPageLoadedEvent::class:
                $event_name = 'begin_checkout';
                break;
            default:
                $event_name = 'view_cart';
                break;
        }

        return $event_name;

    }

    /**
     * @param $lineItems LineItemCollection
     */
    private function getPromotionCode($lineItems)
    {
        foreach ($lineItems as $lineItem) {

            $payload = $lineItem->getPayload();

            if (isset($payload['discountType'])) {
                return $payload['code'] ?? '';
            }

        }
    }

}
