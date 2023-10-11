<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Customer;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Value\Decrement;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Receiver;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Contact;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Subscriber;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\SubscriberRepository;
use DateTime;
use Exception;

/**
 * Class SubscriberService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Customer
 */
class SubscriberService extends BaseReceiverService
{
    public const OPT_IN = 'optIn';
    public const DIRECT = 'direct';

    /**
     * SubscriberService constructor.
     *
     * @param SubscriberRepository $repository
     */
    public function __construct(SubscriberRepository $repository)
    {
        $this->baseRepository = $repository;
    }

    /**
     * Creates subscriber.
     *
     * @param Receiver $receiver
     */
    public function createSubscriber(Receiver $receiver): void
    {
        $this->baseRepository->create($receiver);
    }

    /**
     * Updates subscriber.
     *
     * @param Receiver $receiver
     */
    public function updateSubscriber(Receiver $receiver): void
    {
        $this->baseRepository->update($receiver);
    }

    /**
     * Unsubscribes subscriber.
     *
     * @param Receiver $receiver
     */
    public function unsubscribeSubscriber(Receiver $receiver): void
    {
        $this->baseRepository->unsubscribe($receiver->getEmail());
    }

    /**
     * @inheritDoc
     */
    public function subscribe(Receiver $receiver): void
    {
        $receiver->addModifier(new Decrement('tags', (string)(new Contact('Shopware 6'))));
        $receiver->addTag(new Subscriber('Shopware 6'));
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(Receiver $receiver): void
    {
        $receiver->addModifier(new Decrement('tags', (string)(new Subscriber('Shopware 6'))));
    }

    /**
     * @inheritDoc
     */
    protected function setTags($receiver): void
    {
        $receiver->addTag(new Subscriber('Shopware 6'));
    }

    /**
     * @param $entity
     * @param bool $isServiceSpecificDataRequired
     * @return Receiver
     * @throws Exception
     */
    protected function formatReceiver($entity, bool $isServiceSpecificDataRequired): Receiver
    {
        $receiver = new Receiver();

        $receiver->setEmail($entity['email']);

        if (!empty($entity['first_name']) && !empty($entity['last_name'])) {
            $receiver->setFirstName($entity['first_name']);
            $receiver->setLastName($entity['last_name']);
        } else {
            $receiver->setFirstName($entity['customerFirstName']);
            $receiver->setLastName($entity['customerLastName']);
        }

        if (!empty($entity['zip_code']) && !empty($entity['city']) && !empty($entity['street'])) {
            $receiver->setZip($entity['zip_code']);
            $receiver->setCity($entity['city']);
            $streetAndNumber = explode(' ', $entity['street']);
            $receiver->setStreet(implode(' ', array_slice($streetAndNumber, 0, -1)));
            $receiver->setStreetNumber($streetAndNumber[count($streetAndNumber) - 1]);
        } else {
            $receiver->setZip($entity['customerZip']);
            $receiver->setCity($entity['customerCity']);
            $streetAndNumber = explode(' ', $entity['customerStreet']);
            $receiver->setStreet(implode(' ', array_slice($streetAndNumber, 0, -1)));
            $receiver->setStreetNumber($streetAndNumber[count($streetAndNumber) - 1]);
            $receiver->setCountry($entity['countryName']);
            $receiver->setState($entity['stateName']);
        }

        $receiver->setLanguage($entity['languageName']);

        if (!empty($entity['birthday'])) {
            $birthday = strtotime($entity['birthday']);
            $receiver->setBirthday((new DateTime())->setTimestamp($birthday));
        }

        $receiver->setPhone($entity['phone_number']);

        $registered = strtotime($entity['created_at']);
        $receiver->setRegistered((new DateTime())->setTimestamp($registered));
        $receiver->setSalutation($entity['display_name']);
        $receiver->setShop($entity['salesChannelName']);

        $domainUrls = explode(',', $entity['domainUrls']);
        $receiver->setSource(!empty($domainUrls[0]) ? $domainUrls[0] : 'Shopware 6');

        if (in_array($entity['status'], [self::OPT_IN, self::DIRECT], true)) {
            $active = $entity['confirmed_at'] ? strtotime($entity['confirmed_at']) : strtotime($entity['created_at']);
            $activated = (new DateTime())->setTimestamp($active);
        } else {
            $activated = null;
        }

        $receiver->setActivated($activated);

        if (!empty($entity['customerGroup'])) {
            $tag = new Tag('Shopware 6', $entity['customerGroup']);
            $tag->setType(self::GROUP);
            $receiver->addTag($tag);
        }

        $tag = new Tag('Shopware 6', $entity['salesChannelName']);
        $tag->setType(self::STORE);
        $receiver->addTag($tag);

        if (!empty($entity['tagNames'])) {
            $tags = explode(',', $entity['tagNames']);

            foreach ($tags as $tag) {
                $shopwareTag = new Tag('Shopware 6', $tag);
                $shopwareTag->setType(self::TAG);
                $receiver->addTag($shopwareTag);
            }
        }

        if (!empty($entity['customerTagsNames'])) {
            $customerTags = explode(',', $entity['customerTagsNames']);

            foreach ($customerTags as $customerTag) {
                $shopwareTag = new Tag('Shopware 6', $customerTag);
                $shopwareTag->setType(self::TAG);
                $receiver->addTag($shopwareTag);
            }
        }

        return $receiver;
    }
}
