<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15183;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public static function getTicket(): string
    {
        return 'NEXT-15183';
    }

    public static function getMinVersion(): string
    {
        return '6.2.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.0.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                'onKernelRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_CONTEXT_RESOLVE
            ]
        ];
    }

    public function onKernelRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (
            $request->attributes->get('_route') !== 'store-api.order.state.cancel' &&
	        $request->attributes->get('_route') !== 'store-api.order.state.cancel.major_fallback'
        ) {
            return;
        }

        $orderId = $request->get('orderId');
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if ($orderId === null || ! $context instanceof SalesChannelContext) {
            return;
        }

        if ($context->getCustomer() === null) {
            throw new CustomerNotLoggedInException();
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addFilter(new EqualsFilter('orderCustomer.customerId', $context->getCustomer()->getId()));

        if ($this->orderRepository->searchIds($criteria, $context->getContext())->firstId() === null) {
            $event->setController(function () {
                return new Response('', Response::HTTP_NOT_FOUND);
            });
        }
    }
}
