<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\TimestampsAware;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\Transformer;

/**
 * Class Receiver
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO
 */
class Receiver extends TimestampsAware
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $source;
    /**
     * @var \DateTime
     */
    protected $activated;
    /**
     * @var \DateTime
     */
    protected $registered;
    /**
     * @var \DateTime
     */
    protected $deactivated;
    /**
     * @var string
     */
    protected $salutation;
    /**
     * @var string
     */
    protected $firstName;
    /**
     * @var string
     */
    protected $lastName;
    /**
     * @var string
     */
    protected $street;
    /**
     * @var string
     */
    protected $streetNumber;
    /**
     * @var string
     */
    protected $zip;
    /**
     * @var string
     */
    protected $city;
    /**
     * @var string
     */
    protected $company;
    /**
     * @var string
     */
    protected $state;
    /**
     * @var string
     */
    protected $country;
    /**
     * @var \DateTime
     */
    protected $birthday;
    /**
     * @var string
     */
    protected $phone;
    /**
     * @var string
     */
    protected $shop;
    /**
     * @var string
     */
    protected $customerNumber;
    /**
     * @var string
     */
    protected $language;
    /**
     * @var \DateTime
     */
    protected $lastOrderDate;
    /**
     * @var int
     */
    protected $orderCount;
    /**
     * @var string
     */
    protected $totalSpent;
    /**
     * @var string
     */
    protected $marketingOptInLevel;
    /**
     * List of receiver tags.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[]
     */
    protected $tags;
    /**
     * List of modifiers that will be applied to field(s) when updating receiver.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier[]
     */
    protected $modifiers;
    /**
     * List of order items.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem[]
     */
    protected $orderItems;
    /**
     * Flag that indicates whether is a receiver active
     * Read only, to change active/inactive status, please use activated/deactivated properties
     *
     * @var bool
     */
    protected $active;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return \DateTime | null
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * @param \DateTime | string $activated
     */
    public function setActivated($activated)
    {
        $this->activated = $activated;
    }

    /**
     * @return \DateTime | null
     */
    public function getRegistered()
    {
        return $this->registered;
    }

    /**
     * @param \DateTime $registered | null
     */
    public function setRegistered($registered)
    {
        $this->registered = $registered;
    }

    /**
     * @return \DateTime | null
     */
    public function getDeactivated()
    {
        return $this->deactivated;
    }

    /**
     * @param \DateTime | string $deactivated
     */
    public function setDeactivated($deactivated)
    {
        $this->deactivated = $deactivated;
    }

    /**
     * @return string
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * @param string $salutation
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @param string $streetNumber
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return \DateTime | null
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @param string $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }

    /**
     * @return string
     */
    public function getCustomerNumber()
    {
        return $this->customerNumber;
    }

    /**
     * @param string $customerNumber
     */
    public function setCustomerNumber($customerNumber)
    {
        $this->customerNumber = $customerNumber;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return \DateTime | null
     */
    public function getLastOrderDate()
    {
        return $this->lastOrderDate;
    }

    /**
     * @param \DateTime $lastOrderDate
     */
    public function setLastOrderDate($lastOrderDate)
    {
        $this->lastOrderDate = $lastOrderDate;
    }

    /**
     * @return int
     */
    public function getOrderCount()
    {
        return $this->orderCount;
    }

    /**
     * @param int $orderCount
     */
    public function setOrderCount($orderCount)
    {
        $this->orderCount = $orderCount;
    }

    /**
     * @return string
     */
    public function getTotalSpent()
    {
        return $this->totalSpent;
    }

    /**
     * @param string $totalSpent
     */
    public function setTotalSpent($totalSpent)
    {
        $this->totalSpent = $totalSpent;
    }

    /**
     * @return string
     */
    public function getMarketingOptInLevel()
    {
        return $this->marketingOptInLevel;
    }

    /**
     * @param string $marketingOptInLevel
     */
    public function setMarketingOptInLevel($marketingOptInLevel)
    {
        $this->marketingOptInLevel = $marketingOptInLevel;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag[] $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @param Tag[] | null $tags
     */
    public function addTags($tags)
    {
        $tags = is_array($tags) ? $tags : array();

        if ($this->tags === null) {
            $this->tags = $tags;
        } else {
            $this->tags = array_merge($this->tags, $tags);
        }
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier[]
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Modifier\Modifier[] $modifiers
     */
    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function addModifier(Modifier $modifier)
    {
        $this->modifiers[] = $modifier;
    }

    /**
     * Adds modifiers.
     *
     * @param Modifier[] | null
     */
    public function addModifiers($modifiers)
    {
        $modifiers = is_array($modifiers) ? $modifiers : array();

        if ($this->modifiers === null) {
            $this->modifiers = $modifiers;
        } else {
            $this->modifiers = array_merge($modifiers, $this->modifiers);
        }
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem[]
     */
    public function getOrderItems()
    {
        return $this->orderItems;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem[] $orderItems
     */
    public function setOrderItems($orderItems)
    {
        $this->orderItems = $orderItems;
    }

    /**
     * Adds order item to the internal order items collection.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem $orderItem
     */
    public function addOrderItem(OrderItem $orderItem)
    {
        $this->orderItems[] = $orderItem;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $rawData)
    {
        $receiver = new self();

        $receiver->setId(static::getDataValue($rawData, 'email', null));
        $receiver->setEmail(static::getDataValue($rawData, 'email', null));
        $receiver->setSource(static::getDataValue($rawData, 'source', null));
        $receiver->setActivated(static::getDate(static::getDataValue($rawData, 'activated', null)));
        $receiver->setRegistered(static::getDate(static::getDataValue($rawData, 'registered', null)));
        $receiver->setDeactivated(static::getDate(static::getDataValue($rawData, 'deactivated', null)));

        $attributes = static::getDataValue($rawData, 'global_attributes', array());
        if (!empty($attributes)) {
            $receiver->setSalutation(static::getDataValue($attributes, 'salutation', null));
            $receiver->setFirstName(static::getDataValue($attributes, 'firstname', null));
            $receiver->setLastName(static::getDataValue($attributes, 'lastname', null));
            $receiver->setStreet(static::getDataValue($attributes, 'street', null));
            $receiver->setStreetNumber(static::getDataValue($attributes, 'streetnumber', null));
            $receiver->setZip(static::getDataValue($attributes, 'zip', null));
            $receiver->setCity(static::getDataValue($attributes, 'city', null));
            $receiver->setCompany(static::getDataValue($attributes, 'company', null));
            $receiver->setState(static::getDataValue($attributes, 'state', null));
            $receiver->setCountry(static::getDataValue($attributes, 'country', null));
            $receiver->setBirthday(static::getDate(static::getDataValue($attributes, 'birthday', null)));
            $receiver->setPhone(static::getDataValue($attributes, 'phone', null));
            $receiver->setShop(static::getDataValue($attributes, 'shop', null));
            $receiver->setCustomerNumber(static::getDataValue($attributes, 'customernumber', null));
            $receiver->setLanguage(static::getDataValue($attributes, 'language', null));
            $receiver->setLastOrderDate(static::getDate(static::getDataValue($attributes, 'lastorderdate', null)));
            $receiver->setOrderCount(static::getDataValue($attributes, 'ordercount', null));
            $receiver->setTotalSpent(static::getDataValue($attributes, 'totalspent', null));
            $receiver->setMarketingOptInLevel(static::getDataValue($attributes, 'marketingoptinlevel', null));
        }

        $receiver->setTags(Tag::fromBatch(static::getDataValue($rawData, 'tags', array())));
        $receiver->setModifiers(Modifier::fromBatch(static::getDataValue($rawData, 'modifiers', array())));
        $receiver->setOrderItems(OrderItem::fromBatch(static::getDataValue($rawData, 'orders', array())));

        $receiver->active = static::getDataValue($rawData, 'active', false);

        return $receiver;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $tags = array();
        if ($this->getTags() !== null) {
            foreach ($this->getTags() as $tag) {
                $tags[] = $tag->__toString();
            }
        }

        $result = array(
            'email' => $this->getEmail(),
            'source' => $this->getSource(),
            'activated' => static::getTimestamp($this->getActivated()),
            'registered' => static::getTimestamp($this->getRegistered()),
            'deactivated' => static::getTimestamp($this->getDeactivated()),
            'tags' => $tags,
            'modifiers' => Transformer::batchTransform($this->getModifiers()),
            'orders' => Transformer::batchTransform($this->getOrderItems()),
            'global_attributes' => array(
                'salutation' => $this->getSalutation(),
                'firstname' => $this->getFirstName(),
                'lastname' => $this->getLastName(),
                'street' => $this->getStreet(),
                'streetnumber' => $this->getStreetNumber(),
                'zip' => $this->getZip(),
                'city' => $this->getCity(),
                'company' => $this->getCompany(),
                'state' => $this->getState(),
                'country' => $this->getCountry(),
                'birthday' => static::getTimestamp($this->getBirthday()),
                'phone' => $this->getPhone(),
                'shop' => $this->getShop(),
                'customernumber' => $this->getCustomerNumber(),
                'language' => $this->getLanguage(),
                'lastorderdate' => static::getTimestamp($this->getLastOrderDate()),
                'ordercount' => $this->getOrderCount(),
                'totalspent' => $this->getTotalSpent(),
                'marketingoptinlevel' => $this->getMarketingOptInLevel(),
            ),
        );

        if (!empty($this->id)) {
            $result['id'] = $this->id;
        }

        return $result;
    }
}