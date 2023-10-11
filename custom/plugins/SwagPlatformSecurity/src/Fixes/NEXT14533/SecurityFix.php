<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14533;

use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var SalesChannelContextServiceInterface
     */
    private $contextService;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SalesChannelContextPersister
     */
    private $contextPersister;

    public function __construct(
        SalesChannelContextServiceInterface $contextService,
        EntityRepositoryInterface $orderRepository,
        SalesChannelContextPersister $contextPersister
    ) {
        $this->contextService = $contextService;
        $this->orderRepository = $orderRepository;
        $this->contextPersister = $contextPersister;
    }

    public static function getTicket(): string
    {
        return 'NEXT-14533';
    }

    public static function getMinVersion(): string
    {
        return '6.2.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.5.2';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['resolveContext', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_CONTEXT_RESOLVE_POST],
            ],
        ];
    }

    public function resolveContext(ControllerEvent $event)
    {
        $request = $event->getRequest();

        $route = $request->attributes->get('_route');

        $routes = [
            'frontend.account.edit-order.page',
            'frontend.account.edit-order.update-order'
        ];

        if (!in_array($route, $routes, true)) {
            return;
        }

        $orderId = $request->get('orderId');

        /** @var SalesChannelContext $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context) {
            return;
        }

        $order = $this->orderRepository
            ->search(new Criteria([$orderId]), $context->getContext())
            ->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        if ($context->getCurrency()->getId() === $order->getCurrencyId()) {
            return;
        }

        $this->contextPersister->save(
            $context->getToken(),
            [SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()],
            $context->getSalesChannel()->getId(),
            $context->getCustomer()->getId()
        );

        $context = $this->contextService->get(
            $context->getSalesChannel()->getId(),
            $context->getToken(),
            $context->getContext()->getLanguageId()
        );

        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context->getContext());
    }
}
