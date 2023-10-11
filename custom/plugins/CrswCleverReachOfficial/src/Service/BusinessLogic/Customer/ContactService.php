<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Customer;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value\Decrement;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Contact;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\ContactRepository;
use DateTime;

/**
 * Class ContactService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Customer
 */
class ContactService extends BaseReceiverService
{
    /**
     * ContactService constructor.
     *
     * @param ContactRepository $repository
     */
    public function __construct(ContactRepository $repository)
    {
        $this->baseRepository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function subscribe(Receiver $receiver): void
    {
        $receiver->addModifier(new Decrement('tags', (string)(new Contact('Shopware 6'))));
    }

    /**
     * @inheritDoc
     */
    protected function setTags(Receiver $receiver): void
    {
        $receiver->addTag(new Contact('Shopware 6'));
    }

    /**
     * @param $entity
     * @param bool $isServiceSpecificDataRequired
     *
     * @return Receiver
     */
    protected function formatReceiver($entity, bool $isServiceSpecificDataRequired): Receiver
    {
        $receiver = parent::formatReceiver($entity, $isServiceSpecificDataRequired);

        $receiver->setActivated($entity->getCreatedAt());
        $this->setAddressAndCompanyInfo($entity, $receiver);

        $date = new DateTime();

        if ($entity->getBirthday()) {
            $date->setTimestamp($entity->getBirthday()->getTimestamp());
            $receiver->setBirthday($date);
        }

        if ($entity->getGroup()) {
            $tag = new Tag('Shopware 6', $entity->getGroup()->getTranslation('name'));
            $tag->setType(self::GROUP);
            $receiver->addTag($tag);
        }

        $address = $this->getAddress($entity);
        if ($address) {
            $receiver->setPhone($address->getPhoneNumber());
        }

        $receiver->setCustomerNumber($entity->getCustomerNumber());

        return $receiver;
    }
}