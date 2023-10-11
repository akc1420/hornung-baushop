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
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Builder\Util\PriceFormatter;
use Swag\PayPal\Payment\Handler\PlusHandler;
use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PlusHandlerV1 extends PlusHandler
{
    /**
     * @var PlusHandler
     */
    private $decorated;

    public function __construct(
        PlusHandler $decorated,
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
        $amount = $payPalTransaction->getAmount();
        if (\method_exists($amount, 'getTotal')) {
            $total = $amount->getTotal();
        } else {
            $reflection = new \ReflectionClass($amount);
            $total = $reflection->getProperty('total');
        }
        if ($priceFormatter->formatPrice($transaction->getOrder()->getPrice()->getTotalPrice()) !== $total) {
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), 'PayPal payment total price does not match');
        }

        return $this->decorated->handlePlusPayment($transaction, $dataBag, $salesChannelContext, $customer);
    }
}
