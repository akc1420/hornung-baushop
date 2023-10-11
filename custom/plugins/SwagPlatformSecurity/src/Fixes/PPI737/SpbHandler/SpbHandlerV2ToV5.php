<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\Security\Fixes\PPI737\SpbHandler;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SpbHandlerV2ToV5 extends EcsSpbHandler
{
    /**
     * @var EcsSpbHandler
     */
    private $decorated;

    /**
     * @var OrderResource
     */
    private $orderResource;

    public function __construct(
        EcsSpbHandler $decorated,
        OrderResource $orderResource
    ) {
        $this->decorated = $decorated;
        $this->orderResource = $orderResource;
    }

    public function handleEcsPayment(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext, CustomerEntity $customer): RedirectResponse
    {
        return $this->decorated->handleEcsPayment($transaction, $dataBag, $salesChannelContext, $customer);
    }

    public function handleSpbPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        if ($paypalOrderId === null) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment id is missing');
        }

        try {
            $payment = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannel()->getId());
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment could not be fetched');
        }
        /** @var PurchaseUnit|false $purchaseUnit */
        $purchaseUnit = \current($payment->getPurchaseUnits());
        if ($purchaseUnit === false) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment has no transactions');
        }

        $priceFormatter = new PriceFormatter();
        if ($priceFormatter->formatPrice($transaction->getOrder()->getPrice()->getTotalPrice()) !== $purchaseUnit->getAmount()->getValue()) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment total price does not match');
        }

        return $this->decorated->handleSpbPayment($transaction, $dataBag, $salesChannelContext);
    }
}
