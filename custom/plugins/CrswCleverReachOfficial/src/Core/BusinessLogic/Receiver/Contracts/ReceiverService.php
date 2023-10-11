<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;

interface ReceiverService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Retrieves receiver from the integrated system.
     *
     * @param string $email Receiver identifer.
     *
     * @param bool $isServiceSpecificDataRequired
     *
     * @return Receiver | null
     */
    public function getReceiver($email, $isServiceSpecificDataRequired = false);

    /**
     * Retrieves a batch of receivers.
     *
     * @param string[] $emails List of receiver emails used for retrieval.
     * @param bool $isServiceSpecificDataRequired Specifies whether service should provide service specific data.
     *
     * @return Receiver[]
     */
    public function getReceiverBatch(array $emails, $isServiceSpecificDataRequired = false);

    /**
     * Retrieves list of receiver emails provided by the integration.
     *
     * @return string[]
     */
    public function getReceiverEmails();

    /**
     * Performs subscribe specific actions.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return void
     */
    public function subscribe(Receiver $receiver);

    /**
     * Performs unsubscribe specific actions.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver $receiver
     *
     * @return void
     */
    public function unsubscribe(Receiver $receiver);
}