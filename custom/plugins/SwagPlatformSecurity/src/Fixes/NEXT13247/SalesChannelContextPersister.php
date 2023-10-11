<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13247;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\Security\Components\State;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SalesChannelContextPersister extends \Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $lifetimeInterval;

    /**
     * @var State
     */
    private $state;

    public function __construct(
        array $constructorArgs,
        State $state,
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        ?string $lifetimeInterval = 'P1D'
    ) {
        // @codeCoverageIgnoreStart
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct(... $constructorArgs);
        }
        // @codeCoverageIgnoreEnd

        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->lifetimeInterval = $lifetimeInterval ?? 'P1D';
        $this->state = $state;
    }

    /*
     * @deprecated tag:v6.4.0 - $salesChannelId will be required
     */
    public function save(string $token, array $parameters, ?string $salesChannelId = null, ?string $customerId = null): void
    {
        if (!$this->state->isActive('NEXT-13247')) {
            parent::save($token, $parameters, $salesChannelId, $customerId);
            return;
        }

        $existing = $this->load($token, $salesChannelId, $customerId);

        $parameters = array_replace_recursive($existing, $parameters);

        unset($parameters['token']);

        try {
            $this->connection->executeUpdate(
                'REPLACE INTO sales_channel_api_context (`token`, `payload`, `sales_channel_id`, `customer_id`, `updated_at`)
                VALUES (:token, :payload, :salesChannelId, :customerId, :updatedAt)',
                [
                    'token' => $token,
                    'payload' => json_encode($parameters),
                    'salesChannelId' => $salesChannelId ? Uuid::fromHexToBytes($salesChannelId) : null,
                    'customerId' => $customerId ? Uuid::fromHexToBytes($customerId) : null,
                    'updatedAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        } catch (\Throwable $e) {
            // retry without sales channel id and customer id
            $this->connection->executeUpdate(
                'REPLACE INTO sales_channel_api_context (`token`, `payload`, `updated_at`)
                VALUES (:token, :payload, :updatedAt)',
                [
                    'token' => $token,
                    'payload' => json_encode($parameters),
                    'updatedAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        }
    }

    public function replace(string $oldToken/*, ?SalesChannelContext $context = null*/): string
    {
        $context = null;
        if (\func_num_args() === 2) {
            $context = func_get_arg(1);
        }

        if (!$this->state->isActive('NEXT-13247')) {
            return parent::replace($oldToken, $context);
        }

        $newToken = Random::getAlphanumericString(32);

        $affected = $this->connection->executeUpdate(
            'UPDATE `sales_channel_api_context`
                   SET `token` = :newToken,
                       `updated_at` = :updatedAt
                   WHERE `token` = :oldToken',
            [
                'newToken' => $newToken,
                'oldToken' => $oldToken,
                'updatedAt' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        if ($affected === 0 && \func_num_args() === 2) {
            /** @var SalesChannelContext $context */
            $context = func_get_arg(1);

            $customer = $context->getCustomer();

            try {
                $this->connection->insert('sales_channel_api_context', [
                    'token' => $newToken,
                    'payload' => json_encode([]),
                    'sales_channel_id' => Uuid::fromHexToBytes($context->getSalesChannel()->getId()),
                    'customer_id' => $customer ? Uuid::fromHexToBytes($customer->getId()) : null,
                    'updated_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]);
            } catch (\Throwable $e) {
                // retry without sales channel id and customer id
                $this->connection->insert('sales_channel_api_context', [
                    'token' => $newToken,
                    'payload' => json_encode([]),
                    'updated_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]);
            }
        } elseif ($affected === 0) {
            $this->connection->insert('sales_channel_api_context', [
                'token' => $newToken,
                'payload' => json_encode([]),
                'updated_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        $this->connection->executeUpdate(
            'UPDATE `cart`
                   SET `token` = :newToken
                   WHERE `token` = :oldToken',
            [
                'newToken' => $newToken,
                'oldToken' => $oldToken,
            ]
        );

        // @deprecated tag:v6.4.0.0 - $context will be required
        if (\func_num_args() === 2) {
            $context = func_get_arg(1);
            $context->assign(['token' => $newToken]);

            if (class_exists(SalesChannelContextTokenChangeEvent::class)) {
                $this->eventDispatcher->dispatch(new SalesChannelContextTokenChangeEvent($context, $oldToken, $newToken));
            }
        }

        return $newToken;
    }

    /*
     * @deprecated tag:v6.4.0 - $salesChannelId will be required
    */
    public function load(string $token, ?string $salesChannelId = null, ?string $customerId = null): array
    {
        if (!$this->state->isActive('NEXT-13247')) {
            return parent::load($token, $salesChannelId, $customerId);
        }

        try {
            $data = $this->fetchContext($token, $salesChannelId, $customerId);
        } catch (\Throwable $e) {
            // try without sales channel and customer id
            $data = $this->fetchContext($token);
        }

        if (empty($data)) {
            return [];
        }

        $customerContext = $salesChannelId && $customerId ? $this->getCustomerContext($data, $salesChannelId, $customerId) : null;

        $context = $customerContext ?? array_shift($data);

        $updatedAt = new \DateTimeImmutable($context['updated_at']);
        $expiredTime = $updatedAt->add(new \DateInterval($this->lifetimeInterval));

        $payload = array_filter(json_decode($context['payload'], true));
        $now = new \DateTimeImmutable();
        $payload['expired'] = $expiredTime < $now;

        if ($customerId) {
            $payload['token'] = $context['token'];
        }

        return $payload;
    }

    private function fetchContext(string $token, string $salesChannelId = null, string $customerId = null): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('*');
        $qb->from('sales_channel_api_context');

        if ($salesChannelId !== null) {
            $qb->where('sales_channel_id = :salesChannelId');
            $qb->setParameter(':salesChannelId', Uuid::fromHexToBytes($salesChannelId));

            if ($customerId !== null) {
                $qb->andWhere('token = :token OR customer_id = :customerId');
                $qb->setParameter(':token', $token);
                $qb->setParameter(':customerId', Uuid::fromHexToBytes($customerId));
                $qb->setMaxResults(2);
            } else {
                $qb->andWhere('token = :token');
                $qb->setParameter(':token', $token);
                $qb->setMaxResults(1);
            }
        } else {
            $qb->where('token = :token');
            $qb->setParameter(':token', $token);
            $qb->setMaxResults(1);
        }

        /** @var ResultStatement $statement */
        $statement = $qb->execute();

        if (!$statement instanceof ResultStatement) {
            return [];
        }

        $data = $statement->fetchAll();

        if (empty($data)) {
            return [];
        }

        return $data;
    }

    private function getCustomerContext(array $data, string $salesChannelId, string $customerId): ?array
    {
        foreach ($data as $row) {
            if (!empty($row['customer_id'])
                && Uuid::fromBytesToHex($row['sales_channel_id']) === $salesChannelId
                && Uuid::fromBytesToHex($row['customer_id']) === $customerId
            ) {
                return $row;
            }
        }

        return null;
    }
}
