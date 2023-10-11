<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Transformer\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class AbandonedCartData extends DataTransferObject
{
    /**
     * @var string
     */
    protected $storeId;
    /**
     * @var string
     */
    protected $returnUrl;
    /**
     * @var float
     */
    protected $total;
    /**
     * @var float
     */
    protected $vat;
    /**
     * @var string
     */
    protected $currency;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\CartItem[]
     */
    protected $cartItems;

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
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
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\CartItem[]
     */
    public function getCartItems()
    {
        return $this->cartItems;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\DTO\CartItem[] $cartItems
     */
    public function setCartItems($cartItems)
    {
        $this->cartItems = $cartItems;
    }

    /**
     * Returns array representation of an object.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'storeId' => $this->getStoreId(),
            'returnUrl' => $this->getReturnUrl(),
            'total' => $this->getTotal(),
            'vat' => $this->getVat(),
            'currency' => $this->getCurrency(),
            'cartItems' => Transformer::batchTransform($this->getCartItems()),
        );
    }

    public static function fromArray(array $data)
    {
        $entity = new static();
        $entity->setStoreId(self::getDataValue($data, 'storeId'));
        $entity->setReturnUrl(self::getDataValue($data, 'returnUrl'));
        $entity->setTotal(self::getDataValue($data, 'total', 0.0));
        $entity->setVat(self::getDataValue($data, 'vat', 0.0));
        $entity->setCurrency(self::getDataValue($data, 'currency'));
        $entity->setCartItems(CartItem::fromBatch(self::getDataValue($data, 'cartItems', array())));

        return $entity;
    }
}