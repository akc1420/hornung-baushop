<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Trigger
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO
 */
class Trigger extends DataTransferObject
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
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\AbandonedCart
     */
    protected $cart;

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
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\AbandonedCart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\AbandonedCart $cart
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
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
            'abandonedCartData' => $this->getCart()->toArray(),
        );
    }

    /**
     * Transforms array to an object.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO\Trigger
     */
    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setPoolId(self::getDataValue($data, 'poolid'));
        $entity->setGroupId(self::getDataValue($data, 'groupid'));
        $entity->setCart(
            AbandonedCart::fromArray(self::getDataValue($data, 'abandonedCartData', array()))
        );

        return $entity;
    }
}