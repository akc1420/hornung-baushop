<?php

namespace Swag\Security\Fixes\NEXT19820;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerTokenSubscriber implements EventSubscriberInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var SalesChannelContextPersister
     */
    protected $contextPersister;

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten',
            CustomerEvents::CUSTOMER_DELETED_EVENT => 'onCustomerDeleted',
        ];
    }

    public function __construct(Connection $connection, RequestStack $requestStack, SalesChannelContextPersister $contextPersister)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->contextPersister = $contextPersister;
    }

    public function onCustomerWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            if ($writeResult->getOperation() !== EntityWriteResult::OPERATION_UPDATE) {
                continue;
            }

            $payload = $writeResult->getPayload();
            if (!$this->customerCredentialsChanged($payload)) {
                continue;
            }

            $customerId = $payload['id'];
            $newToken = $this->invalidateUsingSession($customerId);

            $this->revokeAllCustomerTokens($customerId, $newToken);
        }
    }

    public function onCustomerDeleted(EntityDeletedEvent $event): void
    {
        foreach ($event->getIds() as $customerId) {
            $this->revokeAllCustomerTokens($customerId);
        }
    }

    private function customerCredentialsChanged(array $payload): bool
    {
        return isset($payload['password']);
    }

    private function invalidateUsingSession(string $customerId): ?string
    {
        $master = $this->requestStack->getMasterRequest();

        if (!$master) {
            return null;
        }

        // Is not a storefront request
        if (!$master->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT)) {
            return null;
        }

        /** @var SalesChannelContext $context */
        $context = $master->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        // Not loggedin skip
        if ($context->getCustomer() === null) {
            return null;
        }

        // The written customer is not the same as logged-in. We don't modify the user session
        if ($context->getCustomer()->getId() !== $customerId) {
            return null;
        }

        $token = $context->getToken();

        $newToken = $this->contextPersister->replace($token, $context);

        $context->assign([
            'token' => $newToken,
        ]);

        if (!$master->hasSession()) {
            return null;
        }

        $session = $master->getSession();
        $session->migrate();
        $session->set('sessionId', $session->getId());

        $session->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $newToken);
        $master->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $newToken);

        return $newToken;
    }

    public function revokeAllCustomerTokens(string $customerId, ?string ...$preserveTokens): void
    {
        $preserveTokens = array_filter($preserveTokens);

        $revokeParams = [
            'customerId' => null,
            'billingAddressId' => null,
            'shippingAddressId' => null,
        ];

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->update('sales_channel_api_context')
            ->set('payload', ':payload')
            ->where('JSON_EXTRACT(payload, :customerPath) = :customerId')
            ->setParameter(':payload', json_encode($revokeParams))
            ->setParameter(':customerPath', '$.customerId')
            ->setParameter(':customerId', $customerId);

        // keep tokens valid, which are given in $preserveTokens
        if ($preserveTokens) {
            $qb
                ->andWhere($qb->expr()->notIn('token', ':preserveTokens'))
                ->setParameter(':preserveTokens', $preserveTokens, Connection::PARAM_STR_ARRAY);
        }

        $qb->execute();
    }
}