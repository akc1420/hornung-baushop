<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * Class ContextService
 * @package Sisi\Search\Service
 *  @SuppressWarnings(PHPMD.StaticAccess)
 */

class MultiLanuageServiceService
{
    /**
     * @param string $channelId
     * @return mixed
     */
    public function getAllChannelLanguages(string $channelId, Connection $connection)
    {
        return $connection->createQueryBuilder()
            ->select(['HEX(langtable.language_id)'])
            ->from('sales_channel', 'channel')
            ->join('channel', 'sales_channel_language', 'langtable', 'channel.id = langtable.sales_channel_id')
            ->andWhere('channel.id=UNHEX(:id)')
            ->setParameter('id', $channelId)
            ->execute()->fetchAllAssociative();
    }
}
