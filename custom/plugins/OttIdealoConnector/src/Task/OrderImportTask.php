<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class OrderImportTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'ott.idealo_order_import';
    }

    public static function getDefaultInterval(): int
    {
        return 300; // 5 min
    }
}
