<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class OrderItem
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO
 */
class OrderItem extends DataTransferObject
{
    /**
     * Order id.
     *
     * @var string
     */
    protected $orderId;
    /**
     * Product id.
     *
     * @var string
     */
    protected $productId;
    /**
     * Product name.
     *
     * @var string
     */
    protected $productName;
    /**
     * Product manufacturer
     *
     * @var string
     */
    protected $vendor;
    /**
     * Price
     *
     * @var float
     */
    protected $price;
    /**
     * Currency code.
     *
     * @var string
     */
    protected $currency;
    /**
     * Purchased quantity.
     *
     * @var int
     */
    protected $quantity;
    /**
     * Mailing id.
     *
     * @var string
     */
    protected $mailingId;
    /**
     * Timestamp when order item was created. (Equivalent to order created date).
     *
     * @var int
     */
    protected $stamp;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category[]
     */
    protected $categories;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute[]
     */
    protected $attributes;

    /**
     * OrderItem constructor.
     *
     * @param string $orderId
     * @param string $productId
     * @param string $productName
     */
    public function __construct($orderId, $productId, $productName)
    {
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->productName = $productName;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getMailingId()
    {
        return $this->mailingId;
    }

    /**
     * @param string $mailingId
     */
    public function setMailingId($mailingId)
    {
        $this->mailingId = $mailingId;
    }

    /**
     * @return int
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * @param int $stamp
     */
    public function setStamp($stamp)
    {
        $this->stamp = $stamp;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category[]
     */
    public function getCategories()
    {
        return $this->categories ?: array();
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category\Category[] $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes ?: array();
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Attribute\Attribute[] $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Creates Order Item from array of data.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\OrderItem
     */
    public static function fromArray(array $data)
    {
        $entity = new static(
            static::getDataValue($data, 'order_id'),
            static::getDataValue($data, 'product_id'),
            static::getDataValue($data, 'product')
        );

        $entity->setPrice(static::getDataValue($data, 'price', 0));
        $entity->setCurrency(static::getDataValue($data, 'currency'));
        $entity->setQuantity(static::getDataValue($data, 'amount', static::getDataValue($data, 'quantity', 0)));
        $entity->setMailingId(static::getDataValue($data, 'mailing_id'));
        $entity->setStamp(static::getDataValue($data, 'stamp', null));
        $entity->setCategories(
            Category::fromBatch(
                array_map(
                    function ($name) {
                        return array('value' => $name);
                    },
                    explode(',', static::getDataValue($data, 'product_category'))
                )
            )
        );
        $entity->setAttributes(Attribute::fromBatch(explode(',', static::getDataValue($data, 'attributes'))));

        return $entity;
    }

    /**
     * Transforms Order Item to array representation.
     *
     * @return array
     */
    public function toArray()
    {
        $categories = array_map(
            function (Category $c) {
                return $c->getValue();
            },
            $this->getCategories()
        );

        $attributes = array_map(
            function (Attribute $a) {
                return $a->toString();
            },
            $this->getAttributes()
        );

        $result =  array(
            'order_id' => $this->getOrderId(),
            'product_id' => $this->getProductId(),
            'product' => $this->getProductName(),
            'price' => $this->getPrice(),
            'currency' => $this->getCurrency(),
            'quantity' => $this->getQuantity(),
            'mailing_id' => $this->getMailingId(),
            'product_category' => implode(',', $categories),
            'attributes' => implode(',', $attributes),
        );

        if (!empty($this->stamp)) {
            $result['stamp'] = $this->getStamp();
        }

        return $result;
    }
}