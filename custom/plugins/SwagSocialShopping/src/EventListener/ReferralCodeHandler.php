<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\EventListener;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ReferralCodeHandler implements EventSubscriberInterface
{
    private const REFERRAL_CODE_KEY = 'referralCode';

    private const ENTITIES = [
        CustomerDefinition::ENTITY_NAME,
        OrderDefinition::ENTITY_NAME,
    ];

    private DefinitionInstanceRegistry $registry;

    private RequestStack $requestStack;

    public function __construct(
        DefinitionInstanceRegistry $registry,
        RequestStack $requestStack
    ) {
        $this->registry = $registry;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [[
                'storeReferralCode',
                KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE_POST,
            ]],
            EntityWrittenContainerEvent::class => 'createReferralCodes',
        ];
    }

    public function storeReferralCode(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isStorefrontScope($request)) {
            return;
        }

        $referralCode = $request->get(self::REFERRAL_CODE_KEY);

        if (!$referralCode) {
            return;
        }

        $session = $request->getSession();
        $session->set(self::REFERRAL_CODE_KEY, $referralCode);
    }

    public function createReferralCodes(EntityWrittenContainerEvent $containerEvent): void
    {
        $request = $this->requestStack->getMainRequest();

        if (!$request || !$this->isStorefrontScope($request)) {
            return;
        }

        $referralCode = $this->requestStack->getSession()
            ->get(self::REFERRAL_CODE_KEY);

        $context = $containerEvent->getContext();

        if (!$referralCode || !$this->isValidReferralCode($referralCode, $context)) {
            return;
        }

        foreach (self::ENTITIES as $name) {
            $event = $containerEvent->getEventByEntityName($name);

            if (!$event) {
                continue;
            }

            $data = $this->getWrittenData($referralCode, $event);

            if (empty($data)) {
                return;
            }

            $repository = $this->registry->getRepository('swag_social_shopping_' . $name);

            $repository->upsert($data, $context);
        }
    }

    private function isValidReferralCode(string $referralCode, Context $context): bool
    {
        if (!Uuid::isValid($referralCode)) {
            return false;
        }

        $repository = $this->registry->getRepository('sales_channel');

        return (bool) $repository->search(new Criteria([$referralCode]), $context)->first();
    }

    private function isStorefrontScope(Request $request): bool
    {
        $scope = $request->attributes
            ->get(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, []);

        if ($scope instanceof RouteScope) {
            $scope = $scope->getScopes();
        }

        return \in_array(StorefrontRouteScope::ID, $scope, true);
    }

    private function getWrittenData(string $referralCode, EntityWrittenEvent $event): array
    {
        $data = [];
        foreach ($event->getWriteResults() as $entityWriteResult) {
            if ($entityWriteResult->getOperation() !== EntityWriteResult::OPERATION_INSERT) {
                continue;
            }

            $payload = $entityWriteResult->getPayload();

            if (empty($payload)) {
                continue;
            }

            $data[] = [
                $event->getEntityName() . 'Id' => $payload['id'],
                $event->getEntityName() . 'VersionId' => $payload['versionId'] ?? null,
                'referralCode' => $referralCode,
            ];
        }

        return $data;
    }
}
