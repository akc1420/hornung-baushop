<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Contracts\ExecutionContextAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\EventProvider;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\EventRegistrationResultRecorder;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\EventRegistrator;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\SubTasks\ObsoleteEventDeleter;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

abstract class RegisterEventTask extends CompositeTask
{
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext
     */
    private $executionContext;

    /**
     * RegisterEventTask constructor.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext $executionContext
     */
    public function __construct($executionContext)
    {
        parent::__construct($this->getSubTasks());

        $this->executionContext = $executionContext;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
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
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        parent::unserialize($unserialized['parent']);
        $this->executionContext = Serializer::unserialize($unserialized['executionContext']);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['executionContext'] = $this->executionContext->toArray();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $entity = parent::fromArray($data);
        $entity->executionContext = ExecutionContext::fromArray($data['executionContext']);

        return $entity;
    }

    /**
     * Provides execution context.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Context\ExecutionContext
     */
    public function getExecutionContext()
    {
        return $this->executionContext;
    }

    /**
     * Registers execution context for subtasks when deserialization is complete.
     */
    public function onUnserialized()
    {
        parent::onUnserialized();

        /** @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ExecutionContextAware $task */
        foreach ($this->tasks as $task) {
            $task->setExecutionContextProvider(array($this, 'getExecutionContext'));
        }
    }

    /**
     * @inheritDoc
     */
    protected function createSubTask($taskKey)
    {
        /** @var ExecutionContextAware $task */
        $task = new $taskKey;
        $task->setExecutionContextProvider(array($this, 'getExecutionContext'));

        return $task;
    }

    /**
     * @return array
     */
    protected function getSubTasks()
    {
        return array(
            EventProvider::CLASS_NAME => 10,
            ObsoleteEventDeleter::CLASS_NAME => 10,
            EventRegistrator::CLASS_NAME => 60,
            EventRegistrationResultRecorder::CLASS_NAME => 20,
        );
    }
}