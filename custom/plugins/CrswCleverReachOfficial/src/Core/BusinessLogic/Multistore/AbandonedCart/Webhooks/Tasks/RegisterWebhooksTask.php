<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Webhooks\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required\AutomationWebhooksService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Random\RandomString;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\EventRegisterResult;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Exceptions\AbortTaskExecutionException;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

class RegisterWebhooksTask extends Task
{
    const CLASS_NAME = __CLASS__;
    const AUTOMATION_EVENT_TYPE = 'automation';
    /**
     * Automation id.
     *
     * @var int
     */
    protected $automationId;

    /**
     * RegisterWebhooksTask constructor.
     *
     * @param int $automationId
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
     * Registers webhook.
     *
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
        if ($automation === null || !$automation->getCondition()) {
            throw new AbortTaskExecutionException("Invalid automation.");
        }

        $this->reportProgress(5);

        // Try to delete already registered event.
        try {
            $this->getProxy()->deleteEvent($automation->getCondition(), self::AUTOMATION_EVENT_TYPE);
        } catch (\Exception $e) {
            // Nothing to do here as event deletion is not necessary.
        }

        $this->reportProgress(70);

        $this->generateVerificationToken($automation);
        $event = $this->getEvent($automation);
        $registrationDetails = $this->getProxy()->registerEvent($event);
        $this->setCallToken($automation, $registrationDetails);

        $this->reportProgress(100);
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
     * Provides event proxy.
     *
     * @return Proxy | object
     */
    protected function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Generates verification token for cart automation.
     *
     * @param CartAutomation $automation
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     */
    private function generateVerificationToken(CartAutomation $automation)
    {
        $automation->setWebhookVerificationToken(RandomString::generate());
        $this->getCartService()->update($automation);
    }

    /**
     * Provides cart automation event.
     *
     * @param CartAutomation $automation
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\Event
     */
    private function getEvent(CartAutomation $automation)
    {
        $event = new Event();
        // Group id in this context is automation condition.
        $event->setGroupId($automation->getCondition());
        $event->setEvent(static::AUTOMATION_EVENT_TYPE);
        $event->setVerificationToken($automation->getWebhookVerificationToken());
        $event->setUrl($this->getWebhookService()->getWebhookUrl($automation->getId()));

        return $event;
    }

    /**
     * Provides automation webhooks service.
     *
     * @return AutomationWebhooksService | object
     */
    private function getWebhookService()
    {
        return ServiceRegister::getService(AutomationWebhooksService::CLASS_NAME);
    }

    /**
     * Sets call token.
     *
     * @param CartAutomation $automation
     * @param EventRegisterResult $registrationDetails
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToUpdateCartException
     */
    private function setCallToken(CartAutomation $automation, EventRegisterResult $registrationDetails)
    {
        $automation->setWebhookCallToken($registrationDetails->getCallToken());
        $this->getCartService()->update($automation);
    }
}