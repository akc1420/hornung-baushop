<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts\DashboardService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Events\InitialSyncCompletedEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components\FieldsSynchronization;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components\GroupSynchronization;
use Crsw\CleverReachOfficial\Core\BusinessLogic\InitialSynchronization\Tasks\Composite\Components\ReceiverSynchronization;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverExportCompleteEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\Events\TaskCompletedEventBus;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class InitialSyncTask extends CompositeTask
{
    /**
     * InitialSyncTask constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getSubTasks());
    }

    public function execute()
    {
        ReceiverEventBus::getInstance()->when(
            ReceiverExportCompleteEvent::CLASS_NAME,
            array($this, 'setReceiverSynchronizationStatics')
        );

        parent::execute();

        TaskCompletedEventBus::getInstance()->fire(new InitialSyncCompletedEvent());
    }

    /**
     * Records receiver synchronization statistics.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverExportCompleteEvent $event
     */
    public function setReceiverSynchronizationStatics(ReceiverExportCompleteEvent $event)
    {
        $dashboard = $this->getDashboardService();
        $dashboard->setSyncStatisticsDisplayed(false);
        $dashboard->setSyncedReceiversCount($event->getSynchronizedReceiversCount());
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey;
    }

    /**
     * Retrieves dashboard service.
     *
     * @return DashboardService
     */
    protected function getDashboardService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(DashboardService::CLASS_NAME);
    }

    private function getSubTasks()
    {
        return array(
            GroupSynchronization::CLASS_NAME => 10,
            FieldsSynchronization::CLASS_NAME => 5,
            ReceiverSynchronization::CLASS_NAME => 85,
        );
    }
}