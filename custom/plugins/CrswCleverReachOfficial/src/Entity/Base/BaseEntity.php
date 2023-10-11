<?php declare(strict_types=1);


namespace Crsw\CleverReachOfficial\Entity\Base;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Class BaseEntity
 *
 * @package Crsw\CleverReachOfficial\Entity\Base
 */
class BaseEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $shopware_id;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $index_1;
    /**
     * @var string
     */
    protected $index_2;
    /**
     * @var string
     */
    protected $index_3;
    /**
     * @var string
     */
    protected $index_4;
    /**
     * @var string
     */
    protected $index_5;
    /**
     * @var string
     */
    protected $index_6;
    /**
     * @var string
     */
    protected $index_7;
    /**
     * @var string
     */
    protected $index_8;
    /**
     * @var string
     */
    protected $index_9;
    /**
     * @var string
     */
    protected $index_10;
    /**
     * @var string
     */
    protected $data;

    /**
     * @return string
     */
    public function getShopware_id(): string
    {
        return $this->shopware_id;
    }

    /**
     * @param string $shopware_id
     */
    public function setShopware_id(string $shopware_id): void
    {
        $this->shopware_id = $shopware_id;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData(string $data): void
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getIndex_1(): string
    {
        return $this->index_1;
    }

    /**
     * @param string $index_1
     */
    public function setIndex_1(string $index_1): void
    {
        $this->index_1 = $index_1;
    }

    /**
     * @return string
     */
    public function getIndex_2(): string
    {
        return $this->index_2;
    }

    /**
     * @param string $index_2
     */
    public function setIndex_2(string $index_2): void
    {
        $this->index_2 = $index_2;
    }

    /**
     * @return string
     */
    public function getIndex_3(): string
    {
        return $this->index_3;
    }

    /**
     * @param string $index_3
     */
    public function setIndex_3(string $index_3): void
    {
        $this->index_3 = $index_3;
    }

    /**
     * @return string
     */
    public function getIndex_4(): string
    {
        return $this->index_4;
    }

    /**
     * @param string $index_4
     */
    public function setIndex_4(string $index_4): void
    {
        $this->index_4 = $index_4;
    }

    /**
     * @return string
     */
    public function getIndex_5(): string
    {
        return $this->index_5;
    }

    /**
     * @param string $index_5
     */
    public function setIndex_5(string $index_5): void
    {
        $this->index_5 = $index_5;
    }

    /**
     * @return string
     */
    public function getIndex_6(): string
    {
        return $this->index_6;
    }

    /**
     * @param string $index_6
     */
    public function setIndex_6(string $index_6): void
    {
        $this->index_6 = $index_6;
    }

    /**
     * @return string
     */
    public function getIndex_7(): string
    {
        return $this->index_7;
    }

    /**
     * @param string $index_7
     */
    public function setIndex_7(string $index_7): void
    {
        $this->index_7 = $index_7;
    }

    /**
     * @return string
     */
    public function getIndex_8(): string
    {
        return $this->index_8;
    }

    /**
     * @param string $index_8
     */
    public function setIndex_8(string $index_8): void
    {
        $this->index_8 = $index_8;
    }

    /**
     * @return string
     */
    public function getIndex_9(): string
    {
        return $this->index_9;
    }

    /**
     * @param string $index_9
     */
    public function setIndex_9(string $index_9): void
    {
        $this->index_9 = $index_9;
    }

    /**
     * @return string
     */
    public function getIndex_10(): string
    {
        return $this->index_10;
    }

    /**
     * @param string $index_10
     */
    public function setIndex_10(string $index_10): void
    {
        $this->index_10 = $index_10;
    }
}
