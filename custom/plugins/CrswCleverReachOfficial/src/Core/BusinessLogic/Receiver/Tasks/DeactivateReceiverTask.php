<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

class DeactivateReceiverTask extends Task
{
    /**
     * @var string
     */
    private $email;

    /**
     * ActivateReceiverTask constructor.
     *
     * @param string $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->email);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->email = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array('email' => $this->email);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static($array['email']);
    }

    /**
     * Activates a receiver identified by the email.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $receiver = new Receiver();
        $receiver->setEmail($this->email);
        $receiver->setActivated('0');

        $this->reportProgress(50);

        $this->getProxy()->upsertPlus($this->getGroupService()->getId(), array($receiver));

        $this->reportProgress(100);
    }

    /**
     * Retrieves a time provider.
     *
     * @return TimeProvider | object
     */
    private function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }

    /**
     * Retrieves receiver proxy.
     *
     * @return Proxy | object
     */
    private function getProxy()
    {
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * Retrieves group service.
     *
     * @return GroupService | object
     */
    private function getGroupService()
    {
        return ServiceRegister::getService(GroupService::CLASS_NAME);
    }
}