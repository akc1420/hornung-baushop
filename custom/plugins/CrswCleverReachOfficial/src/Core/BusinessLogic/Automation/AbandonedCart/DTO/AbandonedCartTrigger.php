<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class AbandonedCartTrigger extends DataTransferObject
{
    /**
     * @var string
     */
    protected $poolId;
    /**
     * @var string
     */
    protected $groupId;
    /**
     * @var string
     */
    protected $cartId;
    /**
     * @var string
     */
    protected $customerId;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartData
     */
    protected $abandonedCartData;

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
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartData
     */
    public function getAbandonedCartData()
    {
        return $this->abandonedCartData;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartData $abandonedCartData
     */
    public function setAbandonedCartData($abandonedCartData)
    {
        $this->abandonedCartData = $abandonedCartData;
    }

    /**
     * Returns array representation of an object.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'poolid' => $this->getPoolId(),
            'groupid' => $this->getGroupId(),
            'cartId' => $this->getCartId(),
            'customerId' => $this->getCustomerId(),
            'abandonedCartData' => $this->getAbandonedCartData()->toArray(),
        );
    }

    /**
     * Transforms array to an object.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\AbandonedCartTrigger
     */
    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setPoolId(self::getDataValue($data, 'poolid'));
        $entity->setGroupId(self::getDataValue($data, 'groupid'));
        $entity->setCartId(self::getDataValue($data, 'cartId'));
        $entity->setCustomerId(self::getDataValue($data, 'customerId'));
        $entity->setAbandonedCartData(
            AbandonedCartData::fromArray(self::getDataValue($data, 'abandonedCartData', array()))
        );

        return $entity;
    }
}