<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Tasks\Cache\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class RetrieveForms extends Task
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var Form[]
     */
    public $forms;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->forms);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->forms = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'forms' => Transformer::batchTransform($this->forms),
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        $entity = new static();
        $entity->forms = Form::fromBatch($array['forms']);

        return $entity;
    }

    /**
     * Provides forms retrieved from api.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\DTO\Form[]
     */
    public function getForms()
    {
        return $this->forms;
    }

    /**
     * @inheritDoc
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException
     */
    public function execute()
    {
        $this->forms = $this->getFormService()->getForms($this->getGroupService()->getId(), true);
        $this->reportProgress(100);
    }

    /**
     * @return FormService | object
     */
    private function getFormService()
    {
        return ServiceRegister::getService(FormService::CLASS_NAME);
    }

    /**
     * @return GroupService | object
     */
    private function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }
}