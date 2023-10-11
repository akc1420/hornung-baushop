<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Task\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Class DBSchedularEntity
 * @package Sisi\Search\Core\Content\Task\Bundle
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DBSchedularEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var int
     */
    protected $time;



    /**
     * @var int
     */
    protected $days;

    /**
     * @var string
     */
    protected $shop;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $kind;


    /**
     * @var string
     */
    protected $aktive;

    /**
     * @var \DateTimeInterface|null
     */
    protected $lastExecutionTime;

    /**
     * @var \DateTimeInterface
     */
    protected $nextExecutionTime;

    /**
     * @var int
     */
    protected $all;


    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getShop(): string
    {
        return $this->shop;
    }

    /**
     * @param string $shop
     */
    public function setShop(string $shop): void
    {
        $this->shop = $shop;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getAll(): int
    {
        return $this->all;
    }

    /**
     * @param int $all
     */
    public function setAll(int $all): void
    {
        $this->all = $all;
    }


    /**
     * @return \DateTimeInterface|null
     */
    public function getLastExecutionTime()
    {
        return $this->lastExecutionTime;
    }

    public function setLastExecutionTime(?\DateTimeInterface $lastExecutionTime): void
    {
        $this->lastExecutionTime = $lastExecutionTime;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getNextExecutionTime()
    {
        return $this->nextExecutionTime;
    }

    /**
     * @param \DateTimeInterface $nextExecutionTime
     */
    public function setNextExecutionTime(\DateTimeInterface $nextExecutionTime): void
    {
        $this->nextExecutionTime = $nextExecutionTime;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     */
    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return string
     */
    public function getAktive(): string
    {
        return $this->aktive;
    }

    /**
     * @param string $aktive
     */
    public function setAktive(string $aktive): void
    {
        $this->aktive = $aktive;
    }

    /**
     * @return int
     */
    public function getDays(): int
    {
        return $this->days;
    }

    /**
     * @param int $days
     */
    public function setDays(int $days): void
    {
        $this->days = $days;
    }
}
