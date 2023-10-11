<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13664;

use Shopware\Core\SalesChannelRequest;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    public static function getTicket(): string
    {
        return 'NEXT-13664';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.5.1';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLogoutEvent::class => [
                ['updateSessionAfterLogout', 10],
            ]
        ];
    }

    public function updateSessionAfterLogout(): void
    {
        $master = $this->requestStack->getMasterRequest();
        if (!$master) {
            return;
        }

        if (!$master->attributes->get(SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST)) {
            return;
        }

        if (!$master->hasSession()) {
            return;
        }

        $session = $master->getSession();
        $session->invalidate();
    }
}
