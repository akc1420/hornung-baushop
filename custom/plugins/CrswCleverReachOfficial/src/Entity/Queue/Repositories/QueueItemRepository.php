<?php

namespace Crsw\CleverReachOfficial\Entity\Queue\Repositories;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Interfaces\QueueItemRepository as BaseQueueItemRepository;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Utility\IndexHelper;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\QueueItem;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use JsonException;

/**
 * Class QueueItemRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Queue\Repositories
 */
class QueueItemRepository extends BaseRepository implements BaseQueueItemRepository
{
    /**
     * Finds list of earliest queued queue items per queue for given priority.
     * Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *      - Only queue items with given priority can be retrieved.
     *
     * @param int $priority Queue item priority priority.
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     * @throws \Doctrine\DBAL\Exception
     */
    public function findOldestQueuedItems($priority, $limit = 10): array
    {
        $connection = $this->getConnection();

        $ids = $this->getQueueIdsForExecution($priority, $limit);
        $itemIds = "'" . implode("', '", $ids) . "'";

        $sql = "SELECT queueTable.id, queueTable.data
        FROM {$this->getDbName()} as queueTable
        WHERE queueTable.id IN ({$itemIds})
        ORDER BY queueTable.id";

        $rawItems = $connection->fetchAll($sql);

        return $this->inflateQueueItems(!empty($rawItems) ? $rawItems : []);
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *  queue item property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($queueItem, array('status' => 'queued')) should produce query
     *  UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     *
     * @throws JsonException
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemSaveException if queue item could not be saved
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = array()): int
    {
        if ($queueItem->getId()) {
            $this->updateQueueItem($queueItem, $additionalWhere);

            return $queueItem->getId();
        }

        return $this->save($queueItem);
    }

    /**
     * Updates queue item.
     *
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemSaveException
     * @throws JsonException
     */
    protected function updateQueueItem(QueueItem $queueItem, array $additionalWhere): void
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItem->getId());

        foreach ($additionalWhere as $name => $value) {
            if ($value === null) {
                $filter->where($name, Operators::NULL);
            } else {
                $filter->where($name, Operators::EQUALS, $value);
            }
        }

        /** @var QueueItem $item */
        $item = $this->selectOne($filter);
        if ($item === null) {
            throw new QueueItemSaveException("Cannot update queue item with id {$queueItem->getId()}.");
        }

        $this->update($queueItem);
    }

    /**
     * Retrieves queue item ids.
     *
     * @param $priority
     * @param $limit
     *
     * @return array
     */
    protected function getQueueIdsForExecution($priority, $limit): array
    {
        $connection = $this->getConnection();

        $index = $this->getColumnIndexMap();
        $nameColumn = 'index_' . $index['queueName'];
        $statusColumn = 'index_' . $index['status'];
        $priorityColumn = 'index_' . $index['priority'];
        $queuedStatus = QueueItem::QUEUED;
        $inProgressStatus = QueueItem::IN_PROGRESS;

        $runningQueuesQuery = "SELECT DISTINCT {$nameColumn} as name
                                FROM {$this->getDbName()} as q2
                                WHERE q2.{$statusColumn}={$connection->quote($inProgressStatus)}";

        $runningQueueNames = $connection->fetchAll($runningQueuesQuery);

        $sql = "SELECT MIN(t.id) AS id
                FROM {$this->getDbName()} as t
                WHERE t.{$statusColumn}={$connection->quote($queuedStatus)} AND t.{$priorityColumn}={$priority}";

        if (!empty($runningQueueNames)) {
            $names = "'" . implode("', '", array_column($runningQueueNames, 'name')) . "'";
            $sql .= " AND t.{$nameColumn} NOT IN ({$names})";
        }

        $sql .= " GROUP BY t.{$nameColumn}";

        $result = $connection->fetchAll($sql);
        $result = array_column($result, 'id');

        sort($result);

        return array_slice($result, 0, $limit);
    }

    /**
     * Retrieves index column map.
     *
     * @return array
     */
    protected function getColumnIndexMap(): array
    {
        $queueItem = new QueueItem();

        return IndexHelper::mapFieldsToIndexes($queueItem);
    }

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName(): string
    {
        return 'cleverreach_entity';
    }

    /**
     * Inflates queue items.
     *
     * @param array $rawItems
     *
     * @return array
     */
    protected function inflateQueueItems(array $rawItems): array
    {
        $result = [];

        foreach ($rawItems as $rawItem) {
            $item = $this->unserializeEntity($rawItem['data']);

            if (!empty($rawItem['id'])) {
                $item->setId($rawItem['id']);
            }

            $result[] = $item;
        }

        return $result;
    }
}
