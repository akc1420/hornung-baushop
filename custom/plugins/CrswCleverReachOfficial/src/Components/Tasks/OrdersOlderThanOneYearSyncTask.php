<?php


namespace Crsw\CleverReachOfficial\Components\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\ReceiverSyncTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

/**
 * Class OrdersOlderThanOneYearSyncTask
 *
 * @package Crsw\CleverReachOfficial\Components\Tasks
 */
class OrdersOlderThanOneYearSyncTask extends CompositeTask
{
    /**
     * @var ExecutionContext
     */
    private $executionContext;

    /**
     * OrdersOlderThanOneYearSyncTask constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getSubTasks());

        $this->executionContext = new ExecutionContext();
    }

    /**
     * Gets execution context.
     *
     * @return ExecutionContext
     */
    public function getExecutionContext(): ExecutionContext
    {
        return $this->executionContext;
    }

    /**
     * @inheritDoc
     */
    public function serialize(): ?string
    {
        return Serializer::serialize(
            array(
                'parent' => parent::serialize(),
                'executionContext' => Serializer::serialize($this->executionContext),
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized): void
    {
        $unserialized = Serializer::unserialize($serialized);
        parent::unserialize($unserialized['parent']);
        $this->executionContext = Serializer::unserialize($unserialized['executionContext']);
    }

    /**
     * Gets subtasks.
     *
     * @return int[]
     */
    private function getSubTasks(): array
    {
        return array(
            BuyersEmailsResolverTask::class => 20,
            ReceiverSyncTask::class => 80
        );
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        if ($taskKey === BuyersEmailsResolverTask::class) {
            $task = new BuyersEmailsResolverTask();
            $task->setExecutionContextProvider([$this, 'getExecutionContext']);
            return $task;
        }

        return new ReceiverSyncTask($this->executionContext->syncConfiguration);
    }
}