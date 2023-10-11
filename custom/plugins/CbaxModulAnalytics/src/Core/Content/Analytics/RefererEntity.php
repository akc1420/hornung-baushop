<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RefererEntity extends Entity
{
    use EntityIdTrait;

    //noch anpassen

    /**
     * @var \DateTimeInterface|null
     */
    protected $date;

    /**
     * @var string
     */
    protected $referer;

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var int
     */
    protected $counted;

    /**
     * @var string
     */
    protected $deviceType;

    /**
     * @return \DateTimeInterface|null
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @param \DateTimeInterface|null $date
     */
    public function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     */
    public function setReferer(string $referer): void
    {
        $this->referer = $referer;
    }

    /**
     * @return string
     */
    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    /**
     * @param string $salesChannelId
     */
    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @return string
     */
    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    /**
     * @param string $deviceType
     */
    public function setDeviceType(string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    /**
     * @return int
     */
    public function getCounted(): int
    {
        return $this->counted;
    }

    /**
     * @param int $counted
     */
    public function setCounted(int $counted): void
    {
        $this->counted = $counted;
    }
}

