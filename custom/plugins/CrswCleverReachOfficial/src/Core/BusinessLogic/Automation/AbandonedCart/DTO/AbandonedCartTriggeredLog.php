<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\Utility\TimeProvider;

class AbandonedCartTriggeredLog extends Entity
{
    const CLASS_NAME = __CLASS__;
    /**
     * Cart id.
     *
     * @var string
     */
    protected $cartId;
    /**
     * DateTime when the cart has been triggered and email has been sent.
     *
     * @var \DateTime
     */
    protected $triggeredAt;
    /**
     * User context.
     *
     * @var string
     */
    protected $context;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'cartId',
        'context',
        'triggeredAt',
    );

    /**
     * Retrieves cart id.
     *
     * @return string
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * Sets cart id.
     *
     * @param string $cartId
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;
    }

    /**
     * Retrieves triggered at date time.
     *
     * @return \DateTime
     */
    public function getTriggeredAt()
    {
        return $this->triggeredAt;
    }

    /**
     * Sets triggered at date time.
     *
     * @param \DateTime $triggeredAt
     */
    public function setTriggeredAt($triggeredAt)
    {
        $this->triggeredAt = $triggeredAt;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        return array(
            'cartId' => $this->getCartId(),
            'triggeredAt' => $this->getTimeProvider()->serializeDate($this->getTriggeredAt()),
            'context' => $this->getContext(),
        );
    }

    /**
     * Sets raw array data to this entity instance properties.
     *
     * @param array $data Raw array data with keys for class fields. @see self::$fields for field names.
     */
    public function inflate(array $data)
    {
        parent::inflate($data);

        $this->triggeredAt = $this->getTimeProvider()->deserializeDateString($data['triggeredAt']);
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addStringIndex('cartId');
        $map->addStringIndex('context');

        return new EntityConfiguration($map, 'AbandonedCartTriggerLog');
    }

    /**
     * Retrieves time provider.
     *
     * @return TimeProvider | object
     */
    private function getTimeProvider()
    {
        return ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }
}