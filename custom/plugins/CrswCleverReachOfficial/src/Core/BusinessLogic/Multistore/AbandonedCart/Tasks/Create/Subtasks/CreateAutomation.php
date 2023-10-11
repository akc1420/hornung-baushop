<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Tasks\Create\Subtasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationSubmit;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class CreateAutomation extends Task
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var
     */
    protected $automationId;

    /**
     * CreateAutomation constructor.
     *
     * @param $automationId
     */
    public function __construct($automationId)
    {
        $this->automationId = $automationId;
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array('automationId' => $this->automationId);
    }

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static($array['automationId']);
    }

    /**
     * String representation of object
     *
     * @link https://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return Serializer::serialize($this->automationId);
    }

    /**
     * Constructs the object
     *
     * @param string $serialized
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->automationId = Serializer::unserialize($serialized);
    }

    /**
     * Creates automation on the API.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException
     */
    public function execute()
    {

        $automation = $this->getCartService()->find($this->automationId);
        if ($automation === null) {
            throw new AbortTaskExecutionException("Invalid automation.");
        }

        $this->reportProgress(5);

        $automation->setStatus('creating');
        $this->getCartService()->update($automation);

        $this->reportProgress(20);
        $details = $this->createAutomation($automation);

        $this->reportProgress(70);

        $automation->setIsActive($details->isActive());
        $automation->setCondition($details->getId());

        $this->getCartService()->update($automation);

        $this->reportProgress(100);
    }

    /**
     * Creates automation on the API.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation $automation
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\DTO\AutomationDetails
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function createAutomation(CartAutomation $automation)
    {
        $submitData = new AutomationSubmit($automation->getName(), $automation->getStoreId());
        $submitData->setSource($automation->getSource());
        $submitData->setDescription($automation->getDescription());

        return $this->getAutomationProxy()->create($submitData);
    }

    /**
     * Provides cart automation service.
     *
     * @return CartAutomationService | object
     */
    protected function getCartService()
    {
        return ServiceRegister::getService(CartAutomationService::CLASS_NAME);
    }

    /**
     * Provides automation proxy.
     *
     * @return Proxy | object
     */
    protected function getAutomationProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}