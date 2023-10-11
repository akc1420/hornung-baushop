<?php

declare(strict_types=1);

namespace Sisi\Search\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * Class InsertService
 * @package Sisi\Search\Task
 * @SuppressWarnings(PHPMD)
 */
class Task extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'sisi.search_task';
    }

    public static function getDefaultInterval(): int
    {
        return 300; // 5 minutes
    }
}
