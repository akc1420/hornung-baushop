<?php

/**
 * digitvision
 *
 * @category  digitvision
 * @package   Shopware\Plugins\DvsnQuickCart
 * @copyright (c) 2020 digitvision
 */

namespace Dvsn\QuickCart\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Promotion\Cart\PromotionCartAddedInformationError;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Checkout\Promotion\Subscriber\Storefront\StorefrontCartSubscriber;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuickCartController extends StorefrontController
{
    /**
     * ...
     *
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * ...
     *
     * @var ProductLineItemFactory
     */
    private $productLineItemFactory;

    /**
     * ...
     *
     * @var PromotionItemBuilder
     */
    private $promotionItemBuilder;

    /**
     * ...
     *
     * @var CartService
     */
    private $cartService;

    /**
     * ...
     *
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * ...
     *
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        SalesChannelRepositoryInterface $productRepository,
        ProductLineItemFactory $productLineItemFactory,
        PromotionItemBuilder $promotionItemBuilder,
        CartService $cartService,
        SystemConfigService $systemConfigService
    ) {
        // set params
        $this->productRepository = $productRepository;
        $this->productLineItemFactory = $productLineItemFactory;
        $this->promotionItemBuilder = $promotionItemBuilder;
        $this->cartService = $cartService;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * ...
     *
     * Example Urls:
     * - https://www.shop.de/dvsn/quick-cart/add?products=SWDEMO10001
     * - https://www.shop.de/dvsn/quick-cart/add?products=SWDEMO10001,SWDEMO20001&quantity=5,10
     * - https://www.shop.de/dvsn/quick-cart/add?promotion=test
     * - https://www.shop.de/dvsn/quick-cart/add?products=SWDEMO10001,SWDEMO20001&quantity=5,10&promotion=test
     *
     * @RouteScope(scopes={"storefront"})
     * @Route("/dvsn/quick-cart/add",
     *     name="frontend.dvsn.quick-cart/add",
     *     options={"seo"="false"},
     *     methods={"GET"}
     * )
     *
     * @param Request $request
     * @param RequestDataBag $data
     * @param Context $context
     * @param SalesChannelContext $salesChannelContext
     *
     * @return Response
     */
    public function add(Request $request, RequestDataBag $data, Context $context, SalesChannelContext $salesChannelContext): Response
    {
        // not active?
        if ((bool) $this->systemConfigService->get('DvsnQuickCart.config.status') === false) {
            // return to home page
            return $this->redirectToRoute('frontend.home.page');
        }

        // get every parameter
        $productNumbers = (array) explode(',', (string) $request->query->get('products'));
        $quantityArr = (array) explode(',', (string) $request->query->get('quantity'));
        $promotion = (string) $request->query->get('promotion');

        // filter empty values
        $productNumbers = array_filter($productNumbers, function($value) { return (string) $value !== ''; });

        // add products
        $this->addProducts(
            $productNumbers,
            $quantityArr,
            $salesChannelContext
        );

        // add voucher
        $this->addPromotion(
            $promotion,
            $salesChannelContext
        );
        
        // this doesnt work with sw 6.4 anymore

        /*
        // get the cart again
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        // we need to check if the cart is empty when we add a promotion
        // if the cart is empty, we redirect to home page to show the "will be added later" message
        if ($cart->getPrice()->getTotalPrice() === 0.0 && !empty($promotion)) {
            // and redirect to home
            return $this->redirectToRoute('frontend.home.page');
        }
        */

        // redirect to cart by default
        // return $this->redirectToRoute('frontend.checkout.cart.page');
        return $this->redirectToRoute('frontend.home.page');
    }

    /**
     * ...
     *
     * @param array $productNumbers
     * @param array $quantityArr
     * @param SalesChannelContext $salesChannelContext
     */
    private function addProducts(array $productNumbers, array $quantityArr, SalesChannelContext $salesChannelContext): void
    {
        // counter
        $counter = [
            'success' => 0,
            'error' => 0
        ];

        // loop every product
        foreach ($productNumbers as $i => $number) {
            // get the quantity
            $quantity = (isset($quantityArr[$i]) && (int) $quantityArr[$i] > 0)
                ? (int) $quantityArr[$i]
                : 1;

            // find the product
            $criteria = new Criteria();
            $criteria->setLimit(1);
            $criteria->addFilter(new EqualsFilter('productNumber', $number));

            // get the product ids for this one
            $ids = $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();

            // not found?
            if (empty($ids)) {
                // add default error
                $this->addFlash('danger', $this->trans('error.productNotFound', ['%number%' => $number]));

                // count
                $counter['error']++;

                // next
                continue;
            }

            // get the product id
            $productId = array_shift($ids);

            // get the product as line item
            $product = $this->productLineItemFactory->create($productId, ['quantity' => $quantity]);

            // get the cart
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

            // and add the product
            $cart = $this->cartService->add($cart, $product, $salesChannelContext);

            // all good
            $counter['success']++;
        }

        // any products added?
        if ($counter['success'] > 0) {
            // add message
            $this->addFlash('success', $this->trans('checkout.addToCartSuccess', ['%count%' => $counter['success']]));
        }
    }

    /**
     * ...
     *
     * @param string $code
     * @param SalesChannelContext $salesChannelContext
     */
    private function addPromotion(string $code, SalesChannelContext $salesChannelContext): void
    {
        // empty?
        if (empty($code)) {
            // nothing to do
            return;
        }

        // get the cart
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        try {
            $lineItem = $this->promotionItemBuilder->buildPlaceholderItem($code);

            $cart = $this->cartService->add($cart, $lineItem, $salesChannelContext);

            // we basically show all cart errors or notices
            // at the moments its not possible to show success messages with "green" color
            // from the cart...thus it has to be done in the storefront level
            // so if we have an promotion added notice, we simply convert this to
            // a success flash message
            $addedEvents = $cart->getErrors()->filterInstance(PromotionCartAddedInformationError::class);
            if ($addedEvents->count() > 0) {
                $this->addFlash(self::SUCCESS, $this->trans('checkout.codeAddedSuccessful'));
                return;
            }



            // if we have no custom error message above
            // then simply continue with the default display
            // of the cart errors and notices
            $this->traceErrors($cart);
        } catch (\Exception $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }
    }

    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrorsToFlashBag($cart->getErrors()->getNotices(), 'info');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getWarnings(), 'warning');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getErrors(), 'danger');

        $cart->getErrors()->clear();

        return true;
    }

    /**
     * @param Error[] $errors
     */
    private function addCartErrorsToFlashBag(array $errors, string $type): void
    {
        foreach ($errors as $error) {
            $parameters = [];
            foreach ($error->getParameters() as $key => $value) {
                $parameters['%' . $key . '%'] = $value;
            }

            $message = $this->trans('checkout.' . $error->getMessageKey(), $parameters);

            $this->addFlash($type, $message);
        }
    }
}
