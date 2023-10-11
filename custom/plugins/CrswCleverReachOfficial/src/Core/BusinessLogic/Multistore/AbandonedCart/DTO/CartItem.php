<?php

/** @noinspection DuplicatedCode */

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class CartItem
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\DTO
 */
class CartItem extends DataTransferObject
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $sku;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var int
     */
    protected $amount;
    /**
     * @var string
     */
    protected $image;
    /**
     * @var float
     */
    protected $singlePrice;
    /**
     * @var string
     */
    protected $productUrl;

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
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return float
     */
    public function getSinglePrice()
    {
        return $this->singlePrice;
    }

    /**
     * @param float $singlePrice
     */
    public function setSinglePrice($singlePrice)
    {
        $this->singlePrice = $singlePrice;
    }

    /**
     * @return string
     */
    public function getProductUrl()
    {
        return $this->productUrl;
    }

    /**
     * @param string $productUrl
     */
    public function setProductUrl($productUrl)
    {
        $this->productUrl = $productUrl;
    }

    /**
     * Returns array representation of an object.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id' => $this->getId(),
            'sku' => $this->getSku(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'amount' => $this->getAmount(),
            'image' => $this->getImage(),
            'singlePrice' => $this->getSinglePrice(),
            'productUrl' => $this->getProductUrl(),
        );
    }

    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setId(self::getDataValue($data, 'id'));
        $entity->setSku(self::getDataValue($data, 'sku'));
        $entity->setName(self::getDataValue($data, 'name'));
        $entity->setDescription(self::getDataValue($data, 'description'));
        $entity->setAmount(self::getDataValue($data, 'amount', 0));
        $entity->setImage(self::getDataValue($data, 'image'));
        $entity->setSinglePrice(self::getDataValue($data, 'singlePrice', 0.0));
        $entity->setProductUrl(self::getDataValue($data, 'productUrl'));

        return $entity;
    }
}