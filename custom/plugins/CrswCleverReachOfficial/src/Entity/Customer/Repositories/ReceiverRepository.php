<?php

namespace Crsw\CleverReachOfficial\Entity\Customer\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Interface ReceiverRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Customer\Repositories
 */
interface ReceiverRepository
{
    /**
     * Gets number of receivers.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Gets receiver by email.
     *
     * @param string $email
     *
     * @return mixed
     */
    public function getByEmail(string $email);

    /**
     * Retrieves list of receivers identified by provided emails.
     *
     * @param array $emails
     *
     * @return mixed
     */
    public function getByEmails(array $emails);

    /**
     * Retrieves list of receiver emails provided by the integration.
     *
     * @return string[]
     */
    public function getEmails(): array;

    /**
     * Updates receiver.
     *
     * @param Receiver $receiver
     */
    public function update(Receiver $receiver): void;

    /**
     * Creates receiver.
     *
     * @param Receiver $receiver
     */
    public function create(Receiver $receiver): void;

    /**
     * Deletes receiver.
     *
     * @param string $email
     */
    public function delete(string $email): void;
}