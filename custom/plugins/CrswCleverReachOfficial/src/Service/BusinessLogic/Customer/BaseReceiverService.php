<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Customer;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts\ReceiverService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\ReceiverRepository;
use DateTime;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

/**
 * Class BaseReceiverService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Customer
 */
class BaseReceiverService implements ReceiverService
{
    public const TAG = 'Tag';
    public const STORE = 'SalesChannel';
    public const GROUP = 'CustomerGroup';
    /**
     * @var ReceiverRepository
     */
    protected $baseRepository;

    /**
     * @inheritDoc
     */
    public function getReceiver($email, $isServiceSpecificDataRequired = false): ?Receiver
    {
        $receiverEntity = $this->baseRepository->getByEmail($email);

        if (!$receiverEntity) {
            return null;
        }

        $receiver = $this->formatReceiver($receiverEntity, $isServiceSpecificDataRequired);
        $this->setTags($receiver);

        return $receiver;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverBatch(array $emails, $isServiceSpecificDataRequired = false): array
    {
        $validEmails = array_filter(filter_var_array($emails, FILTER_VALIDATE_EMAIL, false));
        if (empty($validEmails)) {
            return [];
        }

        $receiversCollection = $this->baseRepository->getByEmails($validEmails);

        if (!$receiversCollection) {
            return [];
        }

        $receivers = [];

        foreach ($receiversCollection as $item) {
            $receiver = $this->formatReceiver($item, $isServiceSpecificDataRequired);
            $this->setTags($receiver);
            $receivers[] = $receiver;
        }

        return $receivers;
    }

    /**
     * @inheritDoc
     */
    public function getReceiverEmails(): array
    {
        $allEmails = $this->baseRepository->getEmails();

        return array_filter(filter_var_array($allEmails, FILTER_VALIDATE_EMAIL, false));
    }

    /**
     * @inheritDoc
     */
    public function subscribe(Receiver $receiver): void
    {
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(Receiver $receiver): void
    {
    }

    /**
     * Gets number of receivers.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->baseRepository->count();
    }

    /**
     * Sets receiver tags.
     *
     * @param $receiver
     */
    protected function setTags(Receiver $receiver): void
    {
    }

    /**
     * @param mixed $entity
     * @param bool $isServiceSpecificDataRequired
     *
     * @return Receiver
     */
    protected function formatReceiver($entity, bool $isServiceSpecificDataRequired): Receiver
    {
        $receiver = new Receiver();

        $receiver->setEmail($entity->getEmail());

        $date = new DateTime();
        $date->setTimestamp($entity->getCreatedAt()->getTimestamp());
        $receiver->setRegistered($date);

        if ($entity->getSalesChannel()->getDomains()->first()) {
            $receiver->setSource($entity->getSalesChannel()->getDomains()->first()->getUrl());
        }

        $receiver->setSalutation($entity->getSalutation()->getDisplayName());
        $receiver->setFirstName($entity->getFirstName());
        $receiver->setLastName($entity->getLastName());
        $receiver->setShop($entity->getSalesChannel()->getName());
        $receiver->setLanguage($entity->getLanguage()->getName());

        $sourceTags = $entity->getTags();

        foreach ($sourceTags as $tagEntity) {
            $tag = new Tag('Shopware 6', $tagEntity->getName());
            $tag->setType(self::TAG);
            $receiver->addTag($tag);
        }

        if ($entity->getSalesChannel()) {
            $salesChannelTag = new Tag('Shopware 6', $entity->getSalesChannel()->getName());
            $salesChannelTag->setType(self::STORE);
            $receiver->addTag($salesChannelTag);
        }

        return $receiver;
    }

    /**
     * @param Entity $entity
     * @param Receiver $receiver
     */
    protected function setAddressAndCompanyInfo(Entity $entity, Receiver $receiver): void
    {
        $address = $this->getAddress($entity);
        if ($address) {
            $streetAndNumber = explode(' ', $address->getStreet());

            $receiver->setStreet(implode(' ', array_slice($streetAndNumber, 0, -1)));
            $receiver->setStreetNumber($streetAndNumber[count($streetAndNumber) - 1]);
            $receiver->setZip($address->getZipcode());
            $receiver->setCity($address->getCity());
            $receiver->setCompany($address->getCompany());
            $country = $address->getCountry() ? $address->getCountry()->getName() : '';
            $receiver->setCountry($country);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return CustomerAddressEntity
     */
    protected function getAddress(Entity $entity): ?CustomerAddressEntity
    {
        return $entity->getDefaultShippingAddress() ?: $entity->getDefaultBillingAddress();
    }
}
