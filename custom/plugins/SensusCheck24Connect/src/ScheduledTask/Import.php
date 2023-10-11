<?php declare(strict_types=1);

namespace Sensus\Check24Connect\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class Import extends ScheduledTask
{

    public static function getTaskName(): string
    {
        return 'sensus_check24connect.import';
    }

    public static function getDefaultInterval(): int
    {
        return 300;
    }
}