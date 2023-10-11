<?php

namespace Swag\Security\Fixes\NEXT21034;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use function json_decode;
use function json_encode;

class FixSalesChannelContextRestorer extends SalesChannelContextRestorer
{
    /**
     * @var SalesChannelContextRestorer
     */
    protected $original;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(array $originalArgs, SalesChannelContextRestorer $original, Connection $connection)
    {
        parent::__construct(...$originalArgs);
        $this->original = $original;
        $this->connection = $connection;
    }

    public function restore(string $customerId, SalesChannelContext $currentContext): SalesChannelContext
    {
        $context = $this->original->restore($customerId, $currentContext);

        if ($context->getPermissions() !== []) {
            $context->assign(['permissions' => []]);

            $payload = json_decode($this->connection->fetchColumn('SELECT payload FROM sales_channel_api_context WHERE token = ?', [$context->getToken()]), true);
            $payload['permissions'] = [];

            $this->connection->executeUpdate('UPDATE sales_channel_api_context SET payload = ? WHERE token = ?', [
                json_encode($payload),
                $context->getToken()
            ]);
        }

        return $context;
    }

    public function restoreByOrder(string $orderId, Context $context, array $overrideOptions = []): SalesChannelContext
    {
        return $this->original->restoreByOrder($orderId, $context, $overrideOptions);
    }

    public function restoreByCustomer(string $customerId, Context $context, array $overrideOptions = []): SalesChannelContext
    {
        return $this->original->restoreByCustomer($customerId, $context, $overrideOptions);
    }


}
