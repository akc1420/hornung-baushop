<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class Analytics extends ScheduledTask

{
    public static function getTaskName(): string
    {
        return 'cbax.analytics_search_clean_up';
    }

    public static function getDefaultInterval(): int
    {
        return 259200;
    }
}
