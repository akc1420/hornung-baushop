<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Gateway;

use Doctrine\DBAL\Connection;

class OrderStatusGateway
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrdersForStateTransmission(string $state, string $salesChannelId, $isCancellation = false): array
    {
        $statement = <<<'SQL'
            SELECT HEX(o.id) as id, HEX(io.id) as idealo_order_id, io.idealo_transaction_id
            FROM `order` o
            JOIN idealo_order io ON o.id = io.order_id
            LEFT JOIN idealo_order_line_item_status iolis ON io.id = iolis.idealo_order_id
            WHERE (
                iolis.id IS NULL
                    OR
                0 = (SELECT count(iolis2.id) FROM idealo_order_line_item_status iolis2 WHERE iolis.idealo_order_id = iolis2.idealo_order_id AND iolis2.status = :lineItemStatus)
            )
            AND o.sales_channel_id = UNHEX(:salesChannelId)
            AND o.state_id = UNHEX(:state)
            SQL;

        $preparedStatement = $this->connection->prepare($statement);
        $preparedStatement->bindValue('state', $state, \PDO::PARAM_STR);
        $preparedStatement->bindValue('salesChannelId', $salesChannelId, \PDO::PARAM_STR);
        $preparedStatement->bindValue('lineItemStatus', $isCancellation ? 'cancel' : 'send', \PDO::PARAM_STR);
        $preparedStatement->execute();

        return $preparedStatement->fetchAll();
    }
}
