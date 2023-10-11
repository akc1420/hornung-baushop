<?php

namespace Swag\Security\Fixes\NEXT14533;

use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PaymentService extends \Shopware\Core\Checkout\Payment\PaymentService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SalesChannelContextServiceInterface
     */
    private $contextService;

    /**
     * @var SalesChannelContextPersister
     */
    private $contextPersister;

    /**
     * @var \Shopware\Core\Checkout\Payment\PaymentService
     */
    private $decorated;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        SalesChannelContextServiceInterface $contextService,
        SalesChannelContextPersister $contextPersister,
        \Shopware\Core\Checkout\Payment\PaymentService $decorated
    ) {
        $this->orderRepository = $orderRepository;
        $this->contextService = $contextService;
        $this->contextPersister = $contextPersister;
        $this->decorated = $decorated;
    }

    public function handlePaymentByOrder(
        string $orderId,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        ?string $finishUrl = null,
        ?string $errorUrl = null
    ): ?RedirectResponse
    {
        $order = $this->orderRepository
            ->search(new Criteria([$orderId]), $context->getContext())
            ->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        if ($context->getCurrency()->getId() !== $order->getCurrencyId()) {

            $this->contextPersister->save($context->getToken(), [
                SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()
            ]);

            $context = $this->contextService->get(
                $context->getSalesChannel()->getId(),
                $context->getToken(),
                $context->getContext()->getLanguageId()
            );
        }

        return $this->decorated->handlePaymentByOrder($orderId, $dataBag, $context, $finishUrl, $errorUrl);

    }

    public function finalizeTransaction(string $paymentToken, Request $request, SalesChannelContext $salesChannelContext): TokenStruct
    {
        return $this->decorated->finalizeTransaction($paymentToken, $request, $salesChannelContext);
    }

}