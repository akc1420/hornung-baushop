<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class VisitorsEntity extends Entity
{
    use EntityIdTrait;

    //noch anpassen

    /**
     * @var string
     */
    protected $salesChannelId;

    /**
     * @var \DateTimeInterface|null
     */
    protected $date;

    /**
     * @var int
     */
    protected $pageImpressions;

    /**
     * @var int
     */
    protected $uniqueVisits;

    /**
     * @var string
     */
    protected $deviceType;

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
     * @return int
     */
    public function getPageImpressions(): int
    {
        return $this->pageImpressions;
    }

    /**
     * @param int $pageImpressions
     */
    public function setPageImpressions(int $pageImpressions): void
    {
        $this->pageImpressions = $pageImpressions;
    }

    /**
     * @return int
     */
    public function getUniqueVisits(): int
    {
        return $this->uniqueVisits;
    }

    /**
     * @param int $uniqueVisits
     */
    public function setUniqueVisits(int $uniqueVisits): void
    {
        $this->uniqueVisits = $uniqueVisits;
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
}

