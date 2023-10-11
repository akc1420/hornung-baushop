<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Task;

use Ott\IdealoConnector\Service\CronjobService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class OrderStatusExportTaskHandler extends ScheduledTaskHandler
{
    private CronjobService $cronjobService;

    public function __construct(EntityRepositoryInterface $scheduledTaskRepository, CronjobService $cronjobService)
    {
        parent::__construct($scheduledTaskRepository);

        $this->cronjobService = $cronjobService;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            OrderStatusExportTask::class,
        ];
    }

    public function run(): void
    {
        $this->cronjobService->transferOrderStatesToIdealo(true);
    }
}
