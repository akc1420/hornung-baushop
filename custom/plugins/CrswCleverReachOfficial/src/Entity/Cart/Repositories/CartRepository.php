<?php


namespace Crsw\CleverReachOfficial\Entity\Cart\Repositories;

use Doctrine\DBAL\Connection;

/**
 * Class CartRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Cart\Repositories
 */
class CartRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * CartRepository constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $cartId
     *
     * @return mixed|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCart(string $cartId)
    {
        $sql = "SELECT cart.price, currency.symbol, sales_channel_translation.name FROM `cart` 
                LEFT JOIN `sales_channel_translation` ON cart.sales_channel_id = sales_channel_translation.sales_channel_id
                LEFT JOIN `currency` ON cart.currency_id = currency.id
                WHERE `token` =:cartId";

        $result = $this->connection->executeQuery($sql, ['cartId' => $cartId])->fetchAll();

        return !empty($result[0]) ? $result[0] : null;
    }
}
