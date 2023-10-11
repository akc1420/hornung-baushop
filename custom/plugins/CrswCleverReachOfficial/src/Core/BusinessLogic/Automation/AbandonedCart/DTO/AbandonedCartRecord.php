<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;

class AbandonedCartRecord extends Entity
{
    const CLASS_NAME = __CLASS__;
    /**
     * @var string $context
     */
    protected $context;
    /**
     * @var string
     */
    protected $groupId;
    /**
     * @var string
     */
    protected $poolId;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $cartId;
    /**
     * @var string
     */
    protected $customerId;
    /**
     * @var int
     */
    protected $scheduleId;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger
     */
    protected $trigger;

    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'context',
        'groupId',
        'poolId',
        'email',
        'cartId',
        'customerId',
        'scheduleId',
        'trigger',
    );

    /**
     * Retrieves context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets context.
     *
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param string $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return string
     */
    public function getPoolId()
    {
        return $this->poolId;
    }

    /**
     * @param string $poolId
     */
    public function setPoolId($poolId)
    {
        $this->poolId = $poolId;
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
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * @param string $cartId
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param string $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return int
     */
    public function getScheduleId()
    {
        return $this->scheduleId;
    }

    /**
     * @param int $scheduleId
     */
    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger $trigger
     */
    public function setTrigger($trigger)
    {
        $this->trigger = $trigger;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'context' => $this->getContext(),
            'groupId' => $this->getGroupId(),
            'poolId' => $this->getPoolId(),
            'email' => $this->getEmail(),
            'cartId' => $this->getCartId(),
            'customerId' => $this->getCustomerId(),
            'scheduleId' => $this->getScheduleId(),
            'trigger' => $this->getTrigger()->toArray(),
        );
    }

    public function inflate(array $data)
    {
        parent::inflate($data);
        $this->setTrigger(AbandonedCartTrigger::fromArray($data['trigger']));
    }

    public function getConfig()
    {
        $indexMap = new IndexMap();
        $indexMap->addStringIndex('groupId');
        $indexMap->addStringIndex('poolId');
        $indexMap->addStringIndex('email');
        $indexMap->addStringIndex('cartId');
        $indexMap->addIntegerIndex('scheduleId');
        $indexMap->addStringIndex('context');

        return new EntityConfiguration($indexMap, 'AbandonedCartRecord');
    }
}