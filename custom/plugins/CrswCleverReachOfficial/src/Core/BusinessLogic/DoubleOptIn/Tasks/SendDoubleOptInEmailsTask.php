<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoubleOptInEmail;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Http\Proxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Http\Proxy as ReceiverProxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class SendDoubleOptInEmailsTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Tasks
 */
class SendDoubleOptInEmailsTask extends Task
{
    const INITIAL_PROGRESS_PERCENT = 10;

    /**
     * @var DoubleOptInEmail[]
     */
    protected $emails;
    /**
     * @var Proxy
     */
    protected $proxy;
    /**
     * @var ReceiverProxy
     */
    protected $receiverProxy;

    /**
     * SendDoubleOptInEmailsTask constructor.
     *
     * @param DoubleOptInEmail[] $emails
     */
    public function __construct(array $emails)
    {
        $this->emails = $emails;
    }

    public function serialize()
    {
        return Serializer::serialize($this->emails);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->emails = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        return new static(DoubleOptInEmail::fromBatch($array['emails']));
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'emails' => Transformer::batchTransform($this->emails),
        );
    }

    /**
     * Sends double opt-in email.
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function execute()
    {
        $this->reportProgress(static::INITIAL_PROGRESS_PERCENT);

        $currentProgress = static::INITIAL_PROGRESS_PERCENT;
        $progressStep = count($this->emails) > 0 ?
            (int)((100 - self::INITIAL_PROGRESS_PERCENT) / count($this->emails)) : 0;

        foreach ($this->emails as $key => $email) {
            $groupId = ServiceRegister::getService(GroupService::CLASS_NAME)->getId();
            $receiver = $this->getReceiver($groupId, $email->getEmail());

            if (!$receiver || $receiver->isActive()) {
                $this->inactivateReceiver($email, $groupId);
            } else {
                $this->whitelistInactiveReceiver($email);
            }

            $this->getProxy()->sendDoubleOptInEmail($email);

            unset($this->emails[$key]);

            $currentProgress += $progressStep;
            $this->reportProgress($currentProgress);
        }

        $this->reportProgress(100);
    }

    /**
     * Fetches a receiver from CR API
     *
     * @param string $groupId
     * @param string $email
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver|\Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject|null
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    protected function getReceiver($groupId, $email)
    {
        try {
            $receiver = $this->getReceiverProxy()->getReceiver($groupId, $email);
            $this->reportAlive();
        } catch (HttpRequestException $exception) {
            $receiver = null;
        }

        return $receiver;
    }

    /**
     * Creates receiver as inactive
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoubleOptInEmail $email
     * @param string $groupId
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function inactivateReceiver(DoubleOptInEmail $email, $groupId)
    {
        $receiver = new Receiver();

        $receiver->setEmail($email->getEmail());
        $this->getReceiverProxy()->upsertPlus($groupId, array($receiver));

        $receiver->setActivated('0');
        $this->getReceiverProxy()->upsertPlus($groupId, array($receiver));

        return $receiver;
    }

    /**
     * Removes inactive receiver from a blacklist.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoubleOptInEmail $email
     */
    protected function whitelistInactiveReceiver(DoubleOptInEmail $email)
    {
        try {
            $suffix = $this->getGroupService()->getBlacklistedEmailsSuffix();
            $this->getReceiverProxy()->whitelist($email->getEmail() . $suffix);
        } catch (\Exception $e) {
            Logger::logInfo(
                "Failed to remove receiver from a blacklist because: {$e->getMessage()}.",
                'Core',
                array('trace' => $e->getTraceAsString())
            );
        }

        $this->reportAlive();
    }

    /**
     * @return Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * @return ReceiverProxy
     */
    protected function getReceiverProxy()
    {
        if ($this->receiverProxy === null) {
            $this->receiverProxy = ServiceRegister::getService(ReceiverProxy::CLASS_NAME);
        }

        return $this->receiverProxy;
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
