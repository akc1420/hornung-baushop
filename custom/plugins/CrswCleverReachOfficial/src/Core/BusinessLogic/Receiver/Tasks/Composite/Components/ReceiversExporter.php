<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value\Decrement;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiverExportCompleteEvent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Events\ReceiversSynchronizedEvent;
use Crsw\CleverReachOfficial\Core\Infrastructure\Serializer\Serializer;

class ReceiversExporter extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var int $reconfiguredBatchSize
     */
    private $reconfiguredBatchSize = 0;
    /**
     * @var int $totalNumberOfReceivers
     */
    protected $totalNumberOfReceivers = 0;
    /**
     * @var int $numberOfSynchronizedReceivers
     */
    private $numberOfSynchronizedReceivers = 0;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->reconfiguredBatchSize,
                $this->totalNumberOfReceivers,
                $this->numberOfSynchronizedReceivers,
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        list(
            $this->reconfiguredBatchSize,
            $this->totalNumberOfReceivers,
            $this->numberOfSynchronizedReceivers
            ) = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'reconfiguredBatchSize' => $this->reconfiguredBatchSize,
            'totalNumberOfReceivers' => $this->totalNumberOfReceivers,
            'numberOfSynchronizedReceivers' => $this->numberOfSynchronizedReceivers,
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        $static = new static();
        $static->reconfiguredBatchSize = $data['reconfiguredBatchSize'];
        $static->totalNumberOfReceivers = $data['totalNumberOfReceivers'];
        $static->numberOfSynchronizedReceivers = $data['numberOfSynchronizedReceivers'];

        return $static;
    }

    /**
     * Checks whether task can be reconfigured or not.
     *
     * @return bool
     */
    public function canBeReconfigured()
    {
        return $this->reconfiguredBatchSize !== 1;
    }

    /**
     * Reconfigures task.
     */
    public function reconfigure()
    {
        if ($this->reconfiguredBatchSize === 0) {
            $this->reconfiguredBatchSize = $this->getConfigService()->getSynchronizationBatchSize();
        }

        $this->reconfiguredBatchSize = ceil($this->reconfiguredBatchSize / 2);
    }

    /**
     * Exports receivers to CleverReach api.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Exception
     */
    public function execute()
    {
        $this->initializeNumberOfReceivers();
        $batchOfEmails = $this->getBatchOfEmails();
        while (!empty($batchOfEmails)) {
            $batchOfReceivers = $this->getReceivers($batchOfEmails);

            $this->reportAlive();

            $this->setRemovedTags($batchOfReceivers);
            $this->unsubscribeBlacklistedReceivers($batchOfReceivers);

            $this->reportAlive();

            if (!empty($batchOfReceivers)) {
                $this->getReceiverProxy()->upsertPlus($this->getExecutionContext()->groupId, $batchOfReceivers);
            }

            $this->reportAlive();

            $this->unsetExportedBatch($batchOfEmails);

            ReceiverEventBus::getInstance()->fire(new ReceiversSynchronizedEvent(array_keys($batchOfEmails)));

            $this->numberOfSynchronizedReceivers += count($batchOfEmails);
            $this->reportProgress(min(99, $this->getCurrentProgress()));

            $batchOfEmails = $this->getBatchOfEmails();
        }

        ReceiverEventBus::getInstance()->fire(new ReceiverExportCompleteEvent($this->numberOfSynchronizedReceivers));

        $this->reportProgress(100);
    }

    /**
     * Returns current batch size.
     *
     * @return int
     */
    protected function getBatchSize()
    {
        if ($this->reconfiguredBatchSize !== 0) {
            return $this->reconfiguredBatchSize;
        }

        return $this->getConfigService()->getSynchronizationBatchSize();
    }

    /**
     * Retrieves batch of receivers for synchronization.
     *
     * @return array
     */
    protected function getBatchOfEmails()
    {
        return array_slice($this->getExecutionContext()->receiverEmails, 0, $this->getBatchSize(), true);
    }

    /**
     * Retrieves batch of receivers for the list of receiver emails.
     *
     * @param array $receiverEmailsBatch
     *
     * @return Receiver[]
     *
     */
    private function getReceivers(array &$receiverEmailsBatch)
    {
        $executionContext = $this->getExecutionContext();
        $isServiceSpecificDataRequired = $executionContext->syncConfiguration->isClassSpecificDataRequired();
        $batchOfReceivers = array();

        foreach ($executionContext->services as $service) {
            $emailBatchForService = $this->getEmailsForService($service->getUuid(), $receiverEmailsBatch);

            if (!empty($emailBatchForService)) {
                $receiverService = $this->getReceiverService($service->getService());
                $receivers = $receiverService->getReceiverBatch($emailBatchForService, $isServiceSpecificDataRequired);
                foreach ($receivers as $receiver) {
                    $this->addReceiverToBatchOfReceivers($service, $batchOfReceivers, $receiver);
                }
            }
        }

        return array_values($batchOfReceivers);
    }

    /**
     * Retrieves emails for receiver service.
     *
     * @param $service
     * @param array $batch
     *
     * @return array
     */
    private function getEmailsForService($service, array &$batch)
    {
        $result = array();

        foreach ($batch as $email => $services) {
            if (in_array($service, $services, true)) {
                $result[] = $email;
            }
        }

        return $result;
    }

    /**
     * Prepares receiver from service from export.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Config\SyncService $service
     * @param array $exportBatch
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     */
    private function addReceiverToBatchOfReceivers(SyncService $service, array &$exportBatch, Receiver $receiver)
    {
        if (!array_key_exists($receiver->getEmail(), $exportBatch)) {
            $exportBatch[$receiver->getEmail()] = $receiver;

            return;
        }

        $this->getMerger($service->getMerger())->merge($receiver, $exportBatch[$receiver->getEmail()]);
    }

    /**
     * Sets removed tags.
     *
     * @param Receiver[] $receivers
     */
    private function setRemovedTags(array $receivers)
    {
        $modifiers = $this->getTagModifiers();

        foreach ($receivers as $receiver) {
            $receiver->addModifiers($modifiers);
        }
    }

    /**
     * Retrieves removed tags modifiers.
     *
     * @return array
     */
    private function getTagModifiers()
    {
        $result = array();
        $tagsToRemove = $this->getExecutionContext()->syncConfiguration->getTagsToRemove();

        foreach ($tagsToRemove as $tag) {
            $result[] = new Decrement('tags', (string)$tag);
        }

        return $result;
    }

    /**
     * Unsubscribes blacklisted receivers.
     *
     * @param Receiver[] $receivers
     *
     * @throws \Exception
     */
    protected function unsubscribeBlacklistedReceivers(array &$receivers)
    {
        $executionContext = $this->getExecutionContext();

        foreach ($receivers as $receiver) {
            if (!in_array($receiver->getEmail(), $executionContext->blacklistedEmails, true)) {
                continue;
            }

            if (!empty($executionContext->receiverEmails[$receiver->getEmail()])) {
                foreach ($executionContext->receiverEmails[$receiver->getEmail()] as $serviceId) {
                    $this->unsubscribeReceiver($serviceId, $receiver);
                }
            }

            $this->deactivateReceiver($receiver);
        }
    }

    /**
     * Unsubscribes reciever.
     *
     * @param $serviceId
     * @param $receiver
     */
    protected function unsubscribeReceiver($serviceId, $receiver)
    {
        $service = $this->getExecutionContext()->services[$serviceId];
        $this->getReceiverService($service->getService())->unsubscribe($receiver);
    }

    /**
     * Unsets exported batch.
     *
     * @param array $batch
     */
    protected function unsetExportedBatch(array &$batch)
    {
        foreach ($batch as $email => $value) {
            unset($this->getExecutionContext()->receiverEmails[$email]);
        }
    }

    /**
     * Retrieves current progress.
     *
     * @return int
     */
    protected function getCurrentProgress()
    {
        return ($this->numberOfSynchronizedReceivers * 100.0) / $this->totalNumberOfReceivers;
    }

    /**
     * Retrieves total number of receivers for sync.
     *
     * @return int
     */
    protected function initializeNumberOfReceivers()
    {
        if ($this->totalNumberOfReceivers === 0) {
            $this->totalNumberOfReceivers = count($this->getExecutionContext()->receiverEmails);
        }

        return $this->totalNumberOfReceivers ?: 1;
    }

    /**
     * Deactivates receiver.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @throws \Exception
     */
    private function deactivateReceiver(Receiver $receiver)
    {
        $receiver->setActivated('0');
    }
}