<?php

namespace Swag\Security\Fixes\NEXT19276;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CartLineItemController as CoreCartLineItemController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CartLineItemController extends CoreCartLineItemController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CoreCartLineItemController
     */
    private $inner;

    /**
     * @var RequestStack
     */
    private $stack;

    public function __construct(Session $session, RequestStack $stack, CoreCartLineItemController $inner)
    {
        $this->session = $session;
        $this->inner = $inner;
        $this->stack = $stack;
    }

    public function deleteLineItem(Cart $cart, string $id, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->inner->deleteLineItem($cart, $id, $request, $salesChannelContext);
    }

    public function addPromotion(Cart $cart, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $response = $this->inner->addPromotion($cart, $request, $salesChannelContext);

        $code = $request->request->get('code');

        $this->replaceFlash(
            'danger',
            $this->trans('checkout.promotion-not-found', ['%code%' => $code]),
            $this->trans('checkout.promotion-not-found', ['%code%' => $this->purify((string) $code)])
        );

        return $response;
    }

    public function changeQuantity(Cart $cart, string $id, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->inner->changeQuantity($cart, $id, $request, $salesChannelContext);
    }

    public function addProductByNumber(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $response = $this->inner->addProductByNumber($request, $salesChannelContext);

        $number = $request->request->get('number');

        $this->replaceFlash(
            'danger',
            $this->trans('error.productNotFound', ['%number%' => $number]),
            $this->trans('error.productNotFound', ['%number%' => $this->purify((string) $number)])
        );

        return $response;
    }

    public function addLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->inner->addLineItems($cart, $requestDataBag, $request, $salesChannelContext);
    }

    private function replaceFlash(string $type, string $oldMessage, string $newMessage): void
    {
        $flashBag = $this->getFlashBag();
        $flashes = $flashBag->peek($type);

        $index = \array_search($oldMessage, $flashes);
        if ($index === false) {
            return;
        }

        $flashes[$index] = $newMessage;

        $flashBag->set($type, $flashes);
    }

    private function getFlashBag(): FlashBagInterface
    {
        if (\method_exists($this->stack, 'getSession')) {
            return $this->stack->getSession()->getFlashbag();
        }

        return $this->session->getFlashBag();
    }

    private function purify(string $string): string
    {
        $config = \HTMLPurifier_Config::createDefault();

        $config->set('HTML.AllowedElements', []);
        $config->set('HTML.AllowedAttributes', []);

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($string);
    }
}
