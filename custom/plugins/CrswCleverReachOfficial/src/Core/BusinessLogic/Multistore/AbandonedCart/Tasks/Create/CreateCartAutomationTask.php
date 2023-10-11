<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\Subtasks\CreateAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\Subtasks\FinalizeAutomationCreation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Webhooks\Tasks\RegisterWebhooksTask;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\CompositeTask;

class CreateCartAutomationTask extends CompositeTask
{
    /**
     * Id of the automation.
     *
     * @var int
     */
    public $automationId;

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                'parent' => parent::serialize(),
                'automationId' => $this->automationId,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        parent::unserialize($unserialized['parent']);
        $this->automationId = $unserialized['automationId'];
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $serializedData)
    {
        /** @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\CreateCartAutomationTask $entity */
        $entity = parent::fromArray($serializedData);
        $entity->automationId = $serializedData['automationId'];

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        $result = parent::toArray();
        $result['automationId'] = $this->automationId;

        return $result;
    }

    /**
     * CreateCartAutomationTask constructor.
     *
     * @param int $id
     */
    public function __construct($id = null)
    {
        parent::__construct(array(
            CreateAutomation::CLASS_NAME => 30,
            RegisterWebhooksTask::CLASS_NAME => 60,
            FinalizeAutomationCreation::CLASS_NAME => 10,
        ));

        $this->automationId = $id;
    }

    /**
     * Instantiates subtask.
     *
     * @param string $taskKey
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task
     */
    protected function createSubTask($taskKey)
    {
        return new $taskKey($this->automationId);
    }
}