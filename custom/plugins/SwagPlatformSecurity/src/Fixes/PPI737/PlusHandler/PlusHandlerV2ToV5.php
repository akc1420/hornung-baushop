<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\Security\Fixes\PPI737\PlusHandler;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlusHandlerV2ToV5 extends PlusPuiHandler
{
    /**
     * @var PlusPuiHandler
     */
    private $decorated;

    public function __construct(
        PlusPuiHandler $decorated,
        PaymentResource $paymentResource
    ) {
        $this->decorated = $decorated;
        $this->paymentResource = $paymentResource;
    }

    public function handlePlusPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $paypalPaymentId = $dataBag->get(self::PAYPAL_PAYMENT_ID_INPUT_NAME);
        if ($paypalPaymentId === null) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment id is missing');
        }

        try {
            $payment = $this->paymentResource->get($paypalPaymentId, $salesChannelContext->getSalesChannel()->getId());
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment could not be fetched');
        }
        /** @var Transaction|false $payPalTransaction */
        $payPalTransaction = \current($payment->getTransactions());
        if ($payPalTransaction === false) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment has no transactions');
        }

        $priceFormatter = new PriceFormatter();
        if ($priceFormatter->formatPrice($transaction->getOrder()->getPrice()->getTotalPrice()) !== $payPalTransaction->getAmount()->getTotal()) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment total price does not match');
        }

        return $this->decorated->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
    }

    public function handlePuiPayment(AsyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext, CustomerEntity $customer): Payment
    {
        return $this->decorated->handlePuiPayment($transaction, $salesChannelContext, $customer);
    }

    public function handleFinalizePayment(AsyncPaymentTransactionStruct $transaction, string $salesChannelId, Context $context, string $paymentId, string $payerId, string $partnerAttributionId, bool $orderNumberSendNeeded): void
    {
        $this->decorated->handleFinalizePayment($transaction, $salesChannelId, $context, $paymentId, $payerId, $partnerAttributionId, $orderNumberSendNeeded);
    }
}
